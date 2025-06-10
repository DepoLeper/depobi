<?php
// import_raktari_muveletek.php (Javítva)
set_time_limit(600); ini_set('display_errors', 1); error_reporting(E_ALL);
// date_default_timezone_set('Europe/Budapest');
echo "<h1>Összesített Raktári Műveletek Importáló Szkript</h1><p>Indítva: " . date("Y-m-d H:i:s") . "</p><hr>";
$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9SWE1SdFE2S2xPWWV4amdoZVZtMjV1OXZXTDF4QlkwUVNkYVpOTkV2N2JZYmZFblplb3VseTJycXlHX29XVDRGQUJKeXhMemNJOTJLWTkwbUhMV0dQb0trblJYQ1M1VFpCWm51cU8teno0TW9UNGc9';
$db_host = 'localhost'; $db_name = 'tdepo_vezetoi_dash'; $db_user = 'tdepo_dash_admin'; $db_pass = 'Hammer11!';
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name); if (!$conn) { die("DB HIBA"); } mysqli_set_charset($conn, "utf8mb4");
$ch = curl_init(); curl_setopt_array($ch, [ CURLOPT_URL => $feed_url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 120, ]); $csv_data = curl_exec($ch); if (curl_errno($ch)) { die("cURL HIBA"); } curl_close($ch);
$lines = explode("\n", trim($csv_data)); $inserted_rows = 0; $skipped_rows = 0;
$sql = "INSERT IGNORE INTO `raktari_muveletek` (`muvelet_azonosito`, `kezdes_idopont`, `befejezes_idopont`, `irany`, `tenyleges_termek_db`, `tervezett_termek_db`, `munkatars`, `beszallito`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception("HIBA az SQL parancs előkészítésekor: " . mysqli_error($conn)); }
    $date_formats = ['Y-m-d\TH:i:s.uP', 'Y.m.d. H:i:s', 'Y. m. d. H:i:s', 'Y.m.d. H:i', 'Y. m. d. H:i', 'Y-m-d H:i:s'];
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]); if (empty($line)) continue;
        $values = str_getcsv($line); if (count($values) < 11 || empty($values[1])) { $skipped_rows++; continue; }
        $kezdes_dt = false; $befejezes_dt = false;
        foreach ($date_formats as $f) { if ($kezdes_dt===false) $kezdes_dt=DateTime::createFromFormat($f,trim($values[2]??'')); if ($befejezes_dt===false) $befejezes_dt=DateTime::createFromFormat($f,trim($values[10]??'')); }
        if ($kezdes_dt === false || $befejezes_dt === false) { $skipped_rows++; continue; }

        $muvelet_azonosito = $values[1];
        $kezdes_db_format = $kezdes_dt->format('Y-m-d H:i:s');
        $befejezes_db_format = $befejezes_dt->format('Y-m-d H:i:s');
        $irany = $values[3];
        $tenyleges_db = (int)($values[4] ?? 0);
        $tervezett_db = (int)($values[5] ?? 0);
        $munkatars = $values[6];
        $beszallito = $values[8];

        mysqli_stmt_bind_param($stmt, "ssssiiss", $muvelet_azonosito, $kezdes_db_format, $befejezes_db_format, $irany, $tenyleges_db, $tervezett_db, $munkatars, $beszallito);
        mysqli_stmt_execute($stmt); if(mysqli_stmt_affected_rows($stmt) > 0) { $inserted_rows++; }
    }
    mysqli_commit($conn); mysqli_stmt_close($stmt);
} catch(Exception $e) { mysqli_rollback($conn); die("HIBA: " . $e->getMessage()); }
mysqli_close($conn);
echo "<h2>Kész!</h2><p>Beillesztve: $inserted_rows</p><p>Kihagyva: $skipped_rows</p>";
?>