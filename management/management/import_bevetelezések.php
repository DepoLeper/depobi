<?php
// import_bevetelezések.php (Végleges, Robusztus Dátumkezeléssel)
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Budapest');

echo "<h1>Bevételezések Adatimportáló Szkript</h1><p>Indítva: " . date("Y-m-d H:i:s") . "</p><hr>";
$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9ScXEwMU16aXVvTWxSSTJ2Qnc1SElXZTB1WGlEbG9uOHhjZVM5a00teks3ZHZ0b25aSDJFUjYwbXF4ODJIZUhYVWFObVE5Zi1ZMVkwcG5wNlI4STR1b2JvWnNCaS1wVUtaTVh3ZVdna2E5cG1tOW89';
$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!';
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("DB HIBA"); }
mysqli_set_charset($conn, "utf8mb4");
$ch = curl_init();
curl_setopt_array($ch, [ CURLOPT_URL => $feed_url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 120, ]);
$csv_data = curl_exec($ch);
if (curl_errno($ch)) { die("cURL HIBA"); }
curl_close($ch);
if ($csv_data === false || empty(trim($csv_data))) { die("A letöltött CSV fájl üres."); }
echo "<p>CSV sikeresen letöltve.</p>";

$lines = explode("\n", trim($csv_data));
$inserted_rows = 0;
$skipped_rows = 0;
$sql = "INSERT IGNORE INTO `bevetelezések` (`muvelet_azonosito`, `szallitmany_azonosito`, `allapot`, `beszallito`, `kezdes_idopont`, `befejezes_idopont`, `tenyleges_termek_db`, `tervezett_termek_db`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception("HIBA az SQL parancs előkészítésekor: " . mysqli_error($conn)); }
    
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;
        $values = str_getcsv($line);
        if (count($values) < 10 || empty($values[1])) { $skipped_rows++; continue; }
        
        try {
            $kezdes_dt = new DateTime(trim($values[2] ?? ''));
            $befejezes_dt = new DateTime(trim($values[3] ?? ''));
        } catch (Exception $e) {
            $skipped_rows++;
            continue;
        }

        $muvelet_azonosito = $values[1];
        $szallitmany_azonosito = $values[8];
        $allapot = $values[0];
        $beszallito = $values[9];
        $kezdes_db_format = $kezdes_dt->format('Y-m-d H:i:s');
        $befejezes_db_format = $befejezes_dt->format('Y-m-d H:i:s');
        $tenyleges_db = (int)($values[5] ?? 0);
        $tervezett_db = (int)($values[6] ?? 0);

        mysqli_stmt_bind_param($stmt, "ssssssii", $muvelet_azonosito, $szallitmany_azonosito, $allapot, $beszallito, $kezdes_db_format, $befejezes_db_format, $tenyleges_db, $tervezett_db);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_affected_rows($stmt) > 0) { $inserted_rows++; }
    }
    mysqli_commit($conn);
    mysqli_stmt_close($stmt);
} catch(Exception $e) {
    mysqli_rollback($conn);
    die("HIBA: " . $e->getMessage());
}
mysqli_close($conn);
echo "<h2>Kész!</h2><p>Beillesztve: $inserted_rows</p><p>Kihagyva: $skipped_rows</p>";
?>