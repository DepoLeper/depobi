<?php
// api/get_kiszedesek.php
header('Content-Type: application/json; charset=utf-8');

$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!';

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) { throw new Exception("Adatbázis kapcsolódási hiba."); }
    mysqli_set_charset($conn, "utf8mb4");

    // Az SQL-ben a DATE() függvény a MySQL szerver időzónáját fogja használni
    $sql_overall = "SELECT
                COUNT(*) as taskCount,
                COALESCE(SUM(rendelesek_szama), 0) as totalPicks,
                COALESCE(SUM(tenyleges_termek_db), 0) as totalActualItems,
                COALESCE(SUM(tervezett_termek_db), 0) as totalPlannedItems
            FROM kiszedesek WHERE DATE(kezdes_idopont) BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $sql_overall);
    mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $overall = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
    
    // Alapértelmezett, üres adatstruktúrák
    $charts = ['hourly' => array_fill(0, 24, 0), 'duration' => []];
    
    if($overall && $overall['taskCount'] > 0) {
        $overall['isEmpty'] = false;
        // További lekérdezések csak akkor futnak le, ha van adat
        // ... (a többi lekérdezés, mint korábban) ...
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