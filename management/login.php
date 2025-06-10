<?php
session_start();

$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Kapcsolódás ellenőrzése
if (!$conn) {
    // Éles környezetben ezt inkább egy általános hibaoldalra irányítással vagy naplózással kezelnénk.
    die("Adatbázis kapcsolódási hiba. Kérjük, próbálja meg később. Hiba: " . mysqli_connect_error()); 
}
mysqli_set_charset($conn, "utf8mb4"); 

$login_error = '';

// Ha a felhasználó már be van jelentkezve, irányítsuk át a management oldalra
if (isset($_SESSION['loggedin_user_id'])) {
    header('Location: management.php');
    exit;
}

//Űrlapfeldolgozás
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty(trim($_POST['felhasznalonev'])) || empty(trim($_POST['jelszo']))) {
        $login_error = 'Minden mező kitöltése kötelező!';
    } else {
        $felhasznalonev = trim($_POST['felhasznalonev']);
        $jelszo = trim($_POST['jelszo']);

        $sql = "SELECT id, jelszo_hash FROM felhasznalok WHERE felhasznalonev = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $felhasznalonev;
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($jelszo, $hashed_password)) {
                            // Jelszó helyes
                            // Munkamenet változók beállítása
                            $_SESSION['loggedin_user_id'] = $id;
                            $_SESSION['loggedin_username'] = $felhasznalonev;                            
                            
                            header("location: management.php");
                            exit;
                        } else {
                            $login_error = 'Hibás felhasználónév vagy jelszó.';
                        }
                    }
                } else {
                    $login_error = 'Hibás felhasználónév vagy jelszó.';
                }
            } else {
                $login_error = "Hoppá! Valami hiba történt. Próbáld újra később.";
                // Éles környezetben ezt a hibát naplózni kellene: error_log("MySQLi execute error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_error = "Adatbázis hiba. Próbáld újra később.";
             // Éles környezetben ezt a hibát naplózni kellene: error_log("MySQLi prepare error: " . mysqli_error($conn));
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vezetői Dashboard - Bejelentkezés</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md text-center">
        <h1 class="text-2xl font-semibold text-slate-800 mb-6">Vezetői Dashboard</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
            <div class="mb-4">
                <label for="felhasznalonev" class="sr-only">Felhasználónév</label>
                <input type="text" name="felhasznalonev" id="felhasznalonev" placeholder="Felhasználónév" 
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" required>
            </div>
            <div class="mb-6">
                <label for="jelszo" class="sr-only">Jelszó</label>
                <input type="password" name="jelszo" id="jelszo" placeholder="Jelszó" 
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" required>
            </div>
            <?php if(!empty($login_error)): ?>
                <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($login_error); // Biztonságos kiírás ?></p>
            <?php endif; ?>
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
                Bejelentkezés
            </button>
        </form>
        <p class="mt-6 text-xs text-slate-500">
            <a href="../index.html" class="hover:underline">Vissza a publikus dashboardra</a>
        </p>
    </div>
</body>
</html>