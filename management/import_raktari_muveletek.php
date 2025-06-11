<?php
// import_raktari_muveletek.php (Végleges, Robusztus Dátumkezeléssel)

set_time_limit(600); // Megnövelve 10 percre a biztonság kedvéért
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Összesített Raktári Műveletek Importáló Szkript</h1>";
echo "<p>Szkript indításának időpontja: " . date("Y-m-d H:i:s") . "</p><hr>";

// --- Konfiguráció ---
$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9SWE1SdFE2S2xPWWV4amdoZVZtMjV1OXZXTDF4QlkwUVNkYVpOTkV2N2JZYmZFblplb3VseTJycXlHX29XVDRGQUJKeXhMemNJOTJLWTkwbUhMV0dQb0trblJYQ1M1VFpCWm51cU8teno0TW9UNGc9';
$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!';

// --- Adatbázis Kapcsolódás ---
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("ADATBÁZIS KAPCSOLÓDÁSI HIBA: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");
echo "<p>Sikeres adatbázis-kapcsolat.</p>";

// --- CSV Letöltése ---
echo "<p>CSV letöltése a forrásból...</p>";
$ch = curl_init();
curl_setopt_array($ch, [ CURLOPT_URL => $feed_url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 120, ]);
$csv_data = curl_exec($ch);
if (curl_errno($ch)) { $curl_error = curl_error($ch); curl_close($ch); die("cURL HIBA: " . $curl_error); }
curl_close($ch);
if ($csv_data === false || empty(trim($csv_data))) { die("A letöltött CSV fájl üres."); }
echo "<p>CSV sikeresen letöltve.</p>";

// --- Adatfeldolgozás ---
$lines = explode("\n", trim($csv_data));
$total_rows = count($lines) - 1;
$inserted_rows = 0;
$skipped_rows = 0;
echo "<p>Feldolgozás megkezdése. Sorok a feedben (fejléc nélkül): $total_rows</p>";

$sql = "INSERT IGNORE INTO `raktari_muveletek` (`muvelet_azonosito`, `kezdes_idopont`, `befejezes_idopont`, `irany`, `tenyleges_termek_db`, `tervezett_termek_db`, `munkatars`, `beszallito`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception("HIBA az SQL parancs előkészítésekor: " . mysqli_error($conn)); }
    
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        $values = str_getcsv($line);
        if (count($values) < 11 || empty($values[1])) { $skipped_rows++; continue; }
        
        try {
            $kezdes_dt = new DateTime(trim($values[2] ?? ''));
            $befejezes_dt = new DateTime(trim($values[10] ?? ''));
        } catch (Exception $e) {
            $skipped_rows++;
            continue; // Ha a dátum formátuma érvénytelen, kihagyjuk a sort
        }

        $muvelet_azonosito = $values[1];
        $irany = $values[3];
        $tenyleges_db = (int)($values[4] ?? 0);
        $tervezett_db = (int)($values[5] ?? 0);
        $munkatars = $values[6];
        $beszallito = $values[8];
        $kezdes_db_format = $kezdes_dt->format('Y-m-d H:i:s');
        $befejezes_db_format = $befejezes_dt->format('Y-m-d H:i:s');

        mysqli_stmt_bind_param($stmt, "ssssiiss", $muvelet_azonosito, $kezdes_db_format, $befejezes_db_format, $irany, $tenyleges_db, $tervezett_db, $munkatars, $beszallito);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_affected_rows($stmt) > 0) { $inserted_rows++; }
    }
    mysqli_commit($conn);
    mysqli_stmt_close($stmt);
} catch(Exception $e) {
    mysqli_rollback($conn);
    die("HIBA a feldolgozás közben: " . $e->getMessage());
}
mysqli_close($conn);
echo "<h2>Kész!</h2><p>Beillesztve: $inserted_rows</p><p>Kihagyva: $skipped_rows</p>";
?>