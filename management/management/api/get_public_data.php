<?php
// api/get_public_data.php

header('Content-Type: application/json; charset=utf-8');

$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!'; // <-- FONTOS: Ide a helyes jelszó kerüljön!

$response = [];

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Adatbázis kapcsolódási hiba.");
    }
    mysqli_set_charset($conn, "utf8mb4");

    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    $today_date = date('Y-m-d');

    // 1. KISZEDÉSEK ADATAI (JAVÍTVA: rendelesek_szama-t és befejezes_idopont-ot használ)
    $sql_picked = "SELECT COALESCE(SUM(rendelesek_szama), 0) as totalOrders 
                   FROM kiszedesek WHERE befejezes_idopont BETWEEN ? AND ?";
    $stmt_picked = mysqli_prepare($conn, $sql_picked);
    mysqli_stmt_bind_param($stmt_picked, "ss", $today_start, $today_end);
    mysqli_stmt_execute($stmt_picked);
    $response['picked_orders'] = mysqli_stmt_get_result($stmt_picked)->fetch_assoc();
    mysqli_stmt_close($stmt_picked);

    // 2. ÓRÁNKÉNTI KISZEDÉS DIAGRAM ADATAI (JAVÍTVA: rendelesek_szama-t és befejezes_idopont-ot használ)
    $sql_hourly = "SELECT HOUR(befejezes_idopont) as hour, SUM(rendelesek_szama) as orders 
                   FROM kiszedesek WHERE befejezes_idopont BETWEEN ? AND ? GROUP BY hour";
    $stmt_hourly = mysqli_prepare($conn, $sql_hourly);
    mysqli_stmt_bind_param($stmt_hourly, "ss", $today_start, $today_end);
    mysqli_stmt_execute($stmt_hourly);
    $result_hourly = mysqli_stmt_get_result($stmt_hourly);
    $hourly_data = array_fill(0, 24, 0);
    while($row = mysqli_fetch_assoc($result_hourly)){ 
        $hourly_data[$row['hour']] = (int)$row['orders']; 
    }
    $response['picked_orders_chart'] = $hourly_data;
    mysqli_stmt_close($stmt_hourly);

    // 3. CSOMAGOLÁSRA VÁRÓ ADATAI (Változatlan)
    $sql_packable_current = "SELECT COUNT(*) as currentCount FROM csomagolando_rendelesek";
    $response['packable_orders']['currentCount'] = mysqli_query($conn, $sql_packable_current)->fetch_assoc()['currentCount'];
    $sql_packable_max = "SELECT reggeli_max_csomagolando FROM napi_mutatok WHERE datum = ?";
    $stmt_packable_max = mysqli_prepare($conn, $sql_packable_max);
    mysqli_stmt_bind_param($stmt_packable_max, "s", $today_date);
    mysqli_stmt_execute($stmt_packable_max);
    $max_result = mysqli_stmt_get_result($stmt_packable_max)->fetch_assoc();
    $response['packable_orders']['morningMax'] = $max_result['reggeli_max_csomagolando'] ?? 0;
    mysqli_stmt_close($stmt_packable_max);

    // 4. BEVÉTELEZÉS ADATAI (Változatlan)
    $sql_received = "SELECT COUNT(*) as operationCount, GROUP_CONCAT(DISTINCT beszallito SEPARATOR ', ') as suppliers
                     FROM bevetelezések WHERE kezdes_idopont BETWEEN ? AND ?";
    $stmt_received = mysqli_prepare($conn, $sql_received);
    mysqli_stmt_bind_param($stmt_received, "ss", $today_start, $today_end);
    mysqli_stmt_execute($stmt_received);
    $response['received_goods'] = mysqli_stmt_get_result($stmt_received)->fetch_assoc();
    mysqli_stmt_close($stmt_received);

    mysqli_close($conn);
    $response['success'] = true;
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>