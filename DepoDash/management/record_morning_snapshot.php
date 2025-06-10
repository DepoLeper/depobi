<?php
// date_default_timezone_set('Europe/Budapest');
// record_morning_snapshot.php
// Ennek a szkriptnek a feladata, hogy naponta egyszer, reggel lefusson,
// és elmentse a napi kiinduló KPI értékeket.

set_time_limit(60);
// Nem szükséges hibakijelzés a böngészőben, a logfájlba írunk mindent.

$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!'; // <-- FONTOS: Ide a helyes jelszó kerüljön!

$log_message = date("Y-m-d H:i:s") . " - Reggeli mentés indítva.\n";

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Adatbázis kapcsolódási hiba: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");

    // 1. Lépés: Számoljuk meg a jelenleg csomagolásra váró rendeléseket
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM csomagolando_rendelesek");
    if($result === false) {
        throw new Exception("Hiba a csomagolandó rendelések számának lekérdezésekor: " . mysqli_error($conn));
    }
    $row = mysqli_fetch_assoc($result);
    $reggeli_maximum = (int)$row['total'];
    $log_message .= "Jelenleg csomagolásra váró rendelések száma: $reggeli_maximum.\n";

    // 2. Lépés: Mentsük el ezt az értéket a 'napi_mutatok' táblába
    // Az "ON DUPLICATE KEY UPDATE" biztosítja, hogy ha a szkript valamiért többször futna le egy nap,
    // akkor ne hozzon létre új sort, hanem csak frissítse a meglévőt.
    $sql = "INSERT INTO napi_mutatok (datum, reggeli_max_csomagolando) 
            VALUES (CURDATE(), ?) 
            ON DUPLICATE KEY UPDATE reggeli_max_csomagolando = VALUES(reggeli_max_csomagolando)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        throw new Exception("SQL Hiba az adatok mentésekor: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $reggeli_maximum);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $log_message .= "A reggeli maximum sikeresen elmentve/frissítve a mai napra.\n";
    } else {
        $log_message .= "A reggeli maximum már a helyes értékre volt állítva, nem történt módosítás.\n";
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    $log_message .= "Sikeres futás.";

} catch (Exception $e) {
    $log_message .= "KRITIKUS HIBA: " . $e->getMessage() . "\n";
}

// A szkript kimenetét kiírjuk (ezt fogja a cron job a log fájlba írni)
echo $log_message;
?>