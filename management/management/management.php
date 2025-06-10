<?php
// date_default_timezone_set('Europe/Budapest');
session_start();
if (!isset($_SESSION['loggedin_user_id']) || empty($_SESSION['loggedin_username'])) { header("location: login.php"); exit; }
$page = $_GET['page'] ?? 'fooldal'; 
$valid_pages = ['fooldal', 'raktar', 'termekek', 'ceg'];
if (!in_array($page, $valid_pages)) { $page = 'fooldal'; }
$page_titles = [ 'fooldal' => 'Főoldal', 'raktar' => 'Raktár', 'termekek' => 'Termékek', 'ceg' => 'Cégmutatók' ];
$sub_page = '';
if ($page === 'raktar') {
    $valid_sub_pages = ['kiszedesek', 'bevetel', 'munkatarsak'];
    $sub_page = $_GET['sub'] ?? 'kiszedesek';
    if (!in_array($sub_page, $valid_sub_pages)) { $sub_page = 'kiszedesek'; }
    $page_titles['raktar'] = 'Raktár - ' . ucfirst(str_replace('_', ' ', $sub_page));
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_titles[$page]); ?> - Vezetői Irányítópult</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        #sidebar, #main-content { transition: all 0.3s ease-in-out; }
        .sidebar-closed { transform: translateX(-100%); }
        #submenu-raktar { transition: max-height 0.3s ease-in-out; }
        #submenu-raktar-toggle svg { transition: transform 0.3s; }
    </style>
</head>
<body class="bg-slate-100 flex h-screen overflow-hidden">
    <aside id="sidebar" class="bg-gray-800 text-gray-100 w-64 min-h-screen p-4 flex-col fixed inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 flex">
        <div class="text-center mb-10"><h1 class="font-bold text-2xl" style="color: #B688FF;">T-DEPO BI</h1><p class="text-xs text-gray-400">Vezetői Irányítópult</p></div>
        <nav class="flex flex-col gap-y-1 flex-grow">
            <a href="?page=fooldal" class="flex items-center gap-x-3 py-2.5 px-4 rounded-md <?php echo ($page === 'fooldal') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">Főoldal</a>
            <div>
                <button id="submenu-raktar-toggle" class="w-full flex items-center justify-between py-2.5 px-4 rounded-md <?php echo ($page === 'raktar') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">
                    <span class="flex items-center gap-x-3">Raktár</span>
                    <svg id="submenu-raktar-arrow" class="w-4 h-4 <?php echo ($page === 'raktar') ? 'rotate-90' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
                <div id="submenu-raktar" class="pl-4 mt-1 overflow-hidden <?php echo ($page === 'raktar') ? 'max-h-screen' : 'max-h-0'; ?>">
                    <a href="?page=raktar&sub=kiszedesek" class="block text-sm py-2 px-4 rounded-md <?php echo ($sub_page === 'kiszedesek') ? 'bg-gray-600' : 'hover:bg-gray-700'; ?>">Kiszedések</a>
                    <a href="?page=raktar&sub=bevetel" class="block text-sm py-2 px-4 rounded-md <?php echo ($sub_page === 'bevetel') ? 'bg-gray-600' : 'hover:bg-gray-700'; ?>">Bevételezés</a>
                    <a href="?page=raktar&sub=munkatarsak" class="block text-sm py-2 px-4 rounded-md <?php echo ($sub_page === 'munkatarsak') ? 'bg-gray-600' : 'hover:bg-gray-700'; ?>">Munkatársak</a>
                </div>
            </div>
            <a href="?page=termekek" class="flex items-center gap-x-3 py-2.5 px-4 rounded-md <?php echo ($page === 'termekek') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">Termékek</a>
            <a href="?page=ceg" class="flex items-center gap-x-3 py-2.5 px-4 rounded-md <?php echo ($page === 'ceg') ? 'bg-gray-700' : 'hover:bg-gray-700'; ?>">Cég</a>
        </nav>
        <div class="mt-auto"><a href="logout.php" class="flex items-center gap-x-3 py-2.5 px-4 rounded-md text-red-400 hover:bg-red-500 hover:text-white">Kijelentkezés</a></div>
    </aside>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <div id="main-content" class="flex-1 flex flex-col overflow-y-auto">
        <header class="py-4 px-6 bg-white shadow-md flex justify-between items-center sticky top-0 z-30">
            <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 focus:outline-none"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg></button>
            <h2 class="text-xl font-semibold text-slate-700"><?php echo htmlspecialchars($page_titles[$page]); ?></h2>
            <div class="text-sm text-slate-500 hidden sm:block">Bejelentkezve: <strong><?php echo htmlspecialchars($_SESSION['loggedin_username']); ?></strong></div>
        </header>
        <main class="flex-grow p-6">
            <?php
            $page_file_to_include = "page_{$page}.php";
            if ($page === 'raktar') { $page_file_to_include = "page_raktar_{$sub_page}.php"; }
            if (file_exists($page_file_to_include)) { include $page_file_to_include; } 
            else { echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded' role='alert'><strong>Hiba:</strong> A(z) '$page_file_to_include' tartalomfájl nem található.</div>"; }
            ?>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const submenuToggle = document.getElementById('submenu-raktar-toggle');
            const submenu = document.getElementById('submenu-raktar');
            const submenuArrow = document.getElementById('submenu-raktar-arrow');
            const DESKTOP_BREAKPOINT = 1024;

            const setSidebarState = (isOpen) => { const isDesktop = window.innerWidth >= DESKTOP_BREAKPOINT; if (isOpen) { sidebar.classList.remove('-translate-x-full'); if (isDesktop) { mainContent.classList.add('lg:ml-64'); sidebarOverlay.classList.add('hidden'); } else { sidebarOverlay.classList.remove('hidden'); } } else { sidebar.classList.add('-translate-x-full'); mainContent.classList.remove('lg:ml-64'); sidebarOverlay.classList.add('hidden'); } };
            const initializeSidebar = () => { setSidebarState(window.innerWidth >= DESKTOP_BREAKPOINT); };
            
            sidebarToggle.addEventListener('click', (e) => { e.stopPropagation(); setSidebarState(sidebar.classList.contains('-translate-x-full')); });
            sidebarOverlay.addEventListener('click', () => setSidebarState(false));
            document.addEventListener('keydown', (e) => { if (e.key === "Escape") setSidebarState(false); });

            const setSubmenuState = (isOpen) => { if (isOpen) { submenu.style.maxHeight = submenu.scrollHeight + "px"; submenuArrow.style.transform = 'rotate(90deg)'; } else { submenu.style.maxHeight = '0px'; submenuArrow.style.transform = 'rotate(0deg)'; } };
            setSubmenuState(<?php echo json_encode($page === 'raktar'); ?>);
            submenuToggle.addEventListener('click', (e) => { e.preventDefault(); setSubmenuState(submenu.style.maxHeight === '0px'); });
            
            initializeSidebar();
            window.addEventListener('resize', initializeSidebar);
        });
    </script>
</body>
</html>