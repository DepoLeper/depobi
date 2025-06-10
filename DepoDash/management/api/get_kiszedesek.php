<?php
// api/get_kiszedesek.php (Végleges Verzió)
// date_default_timezone_set('Europe/Budapest');
header('Content-Type: application/json; charset=utf-8');

$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!'; // <-- FONTOS: Ide a helyes jelszó kerüljön!

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) { throw new Exception("Adatbázis kapcsolódási hiba."); }
    mysqli_set_charset($conn, "utf8mb4");
    mysqli_query($conn, "SET time_zone = 'Europe/Budapest'");

    $sql_overall = "SELECT
                COUNT(*) as taskCount,
                COALESCE(SUM(rendelesek_szama), 0) as totalPicks,
                COALESCE(SUM(tenyleges_termek_db), 0) as totalActualItems,
                COALESCE(SUM(tervezett_termek_db), 0) as totalPlannedItems,
                COALESCE(SUM(idotartam_mp), 0) as totalDuration
            FROM kiszedesek WHERE DATE(kezdes_idopont) BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $sql_overall);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $overall = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
    
    $charts = ['hourly' => array_fill(0, 24, 0), 'duration' => []];
    
    if($overall && $overall['taskCount'] > 0) {
        $overall['isEmpty'] = false;
        $overall['accuracy'] = $overall['totalPlannedItems'] > 0 ? ($overall['totalActualItems'] / $overall['totalPlannedItems']) * 100 : 100;
        $overall['avgTimePerOp'] = $overall['totalDuration'] / $overall['taskCount'];
        $overall['avgTimePerPick'] = $overall['totalPicks'] > 0 ? $overall['totalDuration'] / $overall['totalPicks'] : 0;
        $overall['avgItemsPerPick'] = $overall['totalPicks'] > 0 ? $overall['totalActualItems'] / $overall['totalPicks'] : 0;

        $sql_hourly = "SELECT HOUR(kezdes_idopont) as hour, SUM(tenyleges_termek_db) as totalItems FROM kiszedesek WHERE DATE(kezdes_idopont) BETWEEN ? AND ? GROUP BY HOUR(kezdes_idopont)";
        $stmt_h = mysqli_prepare($conn, $sql_hourly);
        mysqli_stmt_bind_param($stmt_h, "ss", $startDate, $endDate);
        mysqli_stmt_execute($stmt_h);
        $result_h = mysqli_stmt_get_result($stmt_h);
        while($row = mysqli_fetch_assoc($result_h)){ $charts['hourly'][$row['hour']] = (int)$row['totalItems']; }
        mysqli_stmt_close($stmt_h);

        $sql_duration = "SELECT SUM(CASE WHEN idotartam_mp <= 60 THEN 1 ELSE 0 END) as d_0_60s, SUM(CASE WHEN idotartam_mp > 60 AND idotartam_mp <= 300 THEN 1 ELSE 0 END) as d_1_5m, SUM(CASE WHEN idotartam_mp > 300 AND idotartam_mp <= 900 THEN 1 ELSE 0 END) as d_5_15m, SUM(CASE WHEN idotartam_mp > 900 THEN 1 ELSE 0 END) as d_15p_plus FROM kiszedesek WHERE DATE(kezdes_idopont) BETWEEN ? AND ?";
        $stmt_d = mysqli_prepare($conn, $sql_duration);
        mysqli_stmt_bind_param($stmt_d, "ss", $startDate, $endDate);
        mysqli_stmt_execute($stmt_d);
        $charts['duration'] = mysqli_stmt_get_result($stmt_d)->fetch_assoc();
        mysqli_stmt_close($stmt_d);
    } else {
        $overall['isEmpty'] = true;
    }
    
    mysqli_close($conn);
    echo json_encode(['success' => true, 'overall' => $overall, 'charts' => $charts]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>