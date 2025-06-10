<?php
// import_csomagolando.php
// Feladat: A csomagolásra váró rendelések listájának frissítése a `csomagolando_rendelesek` táblában.

set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Csomagolásra Váró Rendelések Importáló Szkript</h1>";
echo "<p>Szkript indításának időpontja: " . date("Y-m-d H:i:s") . "</p><hr>";

// --- Konfiguráció ---
$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9TQTJDSkk3R0EzQXZiUmZCZF9GV2RXeHJENnJteUN1VkJEN1hYMm11NC12Q0R6OW45cmdma2dWWkFLRFpwT2hlbzNpNXNYNHZJQzdETUExdzlRcTRtVUlOVzJZR3NJMmMxZ2k5aUh6aUswZnJiems9';
$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!'; // Beállítva a megadott jelszó

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
if (curl_errno($ch)) { $curl_error = curl_error($ch); curl_close($ch); die("HIBA: A cURL nem tudta letölteni a CSV fájlt. Hibaüzenet: " . $curl_error); }
curl_close($ch);
echo "<p>CSV sikeresen letöltve.</p>";

// --- Adatfeldolgozás és Feltöltés ---
$lines = explode("\n", trim($csv_data));
$total_rows = count($lines) - 1;
$inserted_rows = 0;
$skipped_rows = 0;
echo "<p>Feldolgozás megkezdése. Összes adatsor a feedben (fejléc nélkül): $total_rows</p>";

$sql_truncate = "TRUNCATE TABLE `csomagolando_rendelesek`";
$sql_insert = "INSERT INTO `csomagolando_rendelesek` (`rendeles_azonosito`, `ugyfel_neve`, `rendeles_allapota`, `cimkek`, `fizetesi_allapot`, `letrehozas_idopontja`, `szallitasi_mod`, `fizetesi_mod`, `netto_ertek`, `penznem`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

mysqli_begin_transaction($conn);
try {
    // 1. Tábla kiürítése
    if (!mysqli_query($conn, $sql_truncate)) {
        throw new Exception("Hiba a `csomagolando_rendelesek` tábla kiürítésekor: " . mysqli_error($conn));
    }
    echo "<p>A tábla sikeresen kiürítve.</p>";
    
    // 2. Új adatok beillesztése
    $stmt = mysqli_prepare($conn, $sql_insert);
    if ($stmt === false) { throw new Exception("HIBA az SQL INSERT parancs előkészítésekor: " . mysqli_error($conn)); }
    
    // A már bevált, robusztus dátumkezelő
    $date_formats = ['Y-m-d\TH:i:s.uP', 'Y.m.d. H:i:s', 'Y. m. d. H:i:s', 'Y.m.d. H:i', 'Y. m. d. H:i', 'Y-m-d H:i:s'];

    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        $values = str_getcsv($line);
        if (count($values) < 10 || empty($values[1])) { $skipped_rows++; continue; }

        $letrehozas_str = trim($values[5] ?? ''); // F oszlop
        $letrehozas_dt = false;
        foreach ($date_formats as $format) {
            if ($letrehozas_dt === false) $letrehozas_dt = DateTime::createFromFormat($format, $letrehozas_str);
        }
        if ($letrehozas_dt === false) {
            // Próbálkozás a new DateTime-mal, mint végső lehetőség ISO 8601 formátumra
            try { $letrehozas_dt = new DateTime($letrehozas_str); } catch (Exception $e) { $skipped_rows++; continue; }
            if ($letrehozas_dt === false) { $skipped_rows++; continue; }
        }

        // Változók létrehozása a biztonságos `bind_param`-hoz
        $rendeles_azonosito = $values[1];
        $ugyfel_neve = $values[0];
        $rendeles_allapota = $values[2];
        $cimkek = $values[3];
        $fizetesi_allapot = $values[4];
        $letrehozas_db_format = $letrehozas_dt->format('Y-m-d H:i:s');
        $szallitasi_mod = $values[6];
        $fizetesi_mod = $values[7];
        $netto_ertek = (int)($values[8] ?? 0);
        $penznem = $values[9];

        mysqli_stmt_bind_param($stmt, "ssssssssis", 
            $rendeles_azonosito, $ugyfel_neve, $rendeles_allapota, $cimkek, $fizetesi_allapot, 
            $letrehozas_db_format, $szallitasi_mod, $fizetesi_mod, $netto_ertek, $penznem
        );
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_affected_rows($stmt) > 0) { $inserted_rows++; }
    }
    
    mysqli_commit($conn);
    mysqli_stmt_close($stmt);
} catch(Exception $e) {
    mysqli_rollback($conn);
    die("HIBA a feldolgozás közben, minden módosítás visszavonva! Hiba: " . $e->getMessage());
}
mysqli_close($conn);

echo "<hr>";
echo "<h2>Feldolgozás Befejezve!</h2>";
echo "<p>Sikeresen beillesztett sorok: <strong style='color: green;'>$inserted_rows</strong></p>";
echo "<p>Formátumhiba vagy egyéb ok miatt kihagyott sorok: <strong style='color: orange;'>$skipped_rows</strong></p>";
?>