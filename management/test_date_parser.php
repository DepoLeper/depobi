<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// date_default_timezone_set('Europe/Budapest');

// Csak a kiszedések feedjét vizsgáljuk
$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9SZUNLX2dFbEIyTXB3SFhNaGEyYW42cFVvcVVsWUZaUi1SaUQyQlFMN2REVzJSU0oxTWVpdjVEejl6U1BQamgxQWJjUlVJREI5VDVEZ3pBSEV5dzltRDdZbzh1Vi1nT3pxRUZtT1hfZFNRbHphRGs9';

echo "<!DOCTYPE html><html><head><title>Dátum Formátum Tesztelő</title><style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';margin:2rem;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:14px;}th{background-color:#f2f2f2;}tr:nth-child(even){background-color:#f9f9f9;}.success{color:green;font-weight:bold;}.fail{color:red;}</style></head><body>";
echo "<h1>Dátum Formátum Tesztelő</h1><p>Ez a szkript a Kiszedések feed 'C' oszlopának első 5 dátumát vizsgálja, hogy megtaláljuk a helyes PHP formátumot.</p>";

$csv_data = @file_get_contents($feed_url);
if ($csv_data === false) {
    die("HIBA: Nem sikerült letölteni a CSV fájlt.");
}

$lines = explode("\n", trim($csv_data));
if (count($lines) < 2) {
    die("HIBA: A CSV fájl üres vagy csak fejlécet tartalmaz.");
}

echo "<h2>A 'C' oszlop első 5 dátumstringjének vizsgálata</h2>";

// A lehetséges formátumok, amiket tesztelünk
$formats_to_try = [
    'Y.m.d. H:i:s',    // pl. 2023.05.20. 14:48:50
    'Y. m. d. H:i:s',  // pl. 2023. 05. 20. 14:48:50 (szóközökkel)
    'Y.m.d. H:i',      // pl. 2023.05.20. 14:48
    'Y. m. d. H:i',    // pl. 2023. 05. 20. 14:48
    'Y-m-d H:i:s',     // pl. 2023-05-20 14:48:50
    'Y-m-d\TH:i:s',    // pl. 2023-05-20T14:48:50 (ISO 8601)
    'Y.m.d G:i',       // pl. 2023.05.20 6:08 (óra vezető nulla nélkül)
    'Y. m. d. G:i',    // pl. 2023. 05. 20. 6:08
];

echo "<table><tr><th>Eredeti String a CSV-ből</th><th>Próbált Formátum</th><th>Siker?</th><th>Értelmezett Eredmény</th></tr>";

// Az első 5 adatsort vizsgáljuk
for ($i = 1; $i < min(6, count($lines)); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    $values = str_getcsv($line);
    $date_string_from_csv = trim($values[2] ?? ''); // 'C' oszlop

    if (empty($date_string_from_csv)) {
        echo "<tr><td>(üres C oszlop)</td><td colspan='3'>-</td></tr>";
        continue;
    }

    $found_a_working_format = false;
    foreach ($formats_to_try as $format) {
        $date_obj = DateTime::createFromFormat($format, $date_string_from_csv);
        $is_success = $date_obj !== false;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($date_string_from_csv) . "</td>";
        echo "<td>" . htmlspecialchars($format) . "</td>";
        if ($is_success) {
            echo "<td class='success'>IGEN</td>";
            echo "<td>" . $date_obj->format('Y-m-d H:i:s') . "</td>";
            $found_a_working_format = true;
        } else {
            echo "<td class='fail'>NEM</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    if (!$found_a_working_format) {
         echo "<tr><td colspan='4' style='background-color:#ffdddd;'><strong>Figyelem: A(z) '".htmlspecialchars($date_string_from_csv)."' stringet egyik tesztelt formátum sem ismerte fel!</strong></td></tr>";
    }
}

echo "</table></body></html>";
?>