<?php // page_raktar_munkatarsak.php ?>
<section>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6"><h2 class="text-xl font-bold text-slate-700">Munkatársi Teljesítmény (Ma)</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase tracking-wider"><tr><th class="py-3 px-4 font-medium">Név</th><th class="py-3 px-4 font-medium text-right">Kiszedett Termék (db)</th><th class="py-3 px-4 font-medium text-right">Kiszedések (db)</th><th class="py-3 px-4 font-medium text-right">Aktív Munkaidő</th><th class="py-3 px-4 font-medium text-right">Átlagos Szünetidő</th><th class="py-3 px-4 font-medium text-right">Hatékonyság (db/aktív óra)</th></tr></thead>
                <tbody id="employeePerformanceTableBody" class="bg-white divide-y divide-gray-200"><tr><td colspan="6" class="p-6 text-center text-gray-500">Adatok betöltése...</td></tr></tbody>
            </table>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            // A teljes JavaScript kód, amit az előző válaszban adtam, ide jön...
            const WAREHOUSE_PERFORMANCE_URL = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9SQ0RjNWV4N09udVZaS1JJbXJmQnV3TFY4WU03Uzg3WVZ4ejFqeE9KdkUwRkFQeGRjby1OSmhsQmEtNkdmdWRxaFBMbGNHMUg0c1hDWHZ2d0lqcm0yel92QTlUak5OQ05fRENWbjdHWElGOTJNRWc9';
            const REFRESH_INTERVAL = 5 * 60 * 1000;
            function formatSeconds(seconds) { if (isNaN(seconds) || seconds < 0) return "00:00:00"; const h = Math.floor(seconds / 3600); const m = Math.floor((seconds % 3600) / 60); const s = Math.floor(seconds % 60); return [ h.toString().padStart(2, '0'), m.toString().padStart(2, '0'), s.toString().padStart(2, '0') ].join(':');}
            function getLocalDateString(dateObj) { const year = dateObj.getFullYear(); const month = String(dateObj.getMonth() + 1).padStart(2, '0'); const day = String(dateObj.getDate()).padStart(2, '0'); return `${year}-${month}-${day}`; }
            async function fetchWarehousePerformanceData() { const response = await fetch(`${WAREHOUSE_PERFORMANCE_URL}&timestamp=${new Date().getTime()}`); if (!response.ok) throw new Error(`Adatforrás hiba (${response.status})`); const csvText = await response.text(); const lines = csvText.trim().split('\n'); if (lines.length <= 1 || (lines.length === 1 && lines[0].trim() === '')) return [];
                const todayStr = getLocalDateString(new Date()); const performanceData = {}; const colStartTime = 1, colEndTime = 2, colDuration = 3, colItems = 5, colEmployee = 6;
                for (let i = 1; i < lines.length; i++) { const values = lines[i].trim().split(','); if (values.length <= colEmployee) continue; const startTime = new Date(values[colStartTime]); if (isNaN(startTime.getTime()) || getLocalDateString(startTime) !== todayStr) continue; const endTime = new Date(values[colEndTime]); const employeeName = values[colEmployee]?.trim(); if (!employeeName) continue;
                    if (!performanceData[employeeName]) { performanceData[employeeName] = { name: employeeName, tasks: [], totalItems: 0, taskCount: 0, totalActiveDuration: 0, }; }
                    const data = performanceData[employeeName]; data.tasks.push({ start: startTime, end: endTime }); data.taskCount++; data.totalItems += parseFloat(values[colItems].replace(',', '.')) || 0; data.totalActiveDuration += parseFloat(values[colDuration].replace(',', '.')) || 0; }
                return Object.values(performanceData).map(emp => {
                    emp.tasks.sort((a, b) => a.start - b.start); const firstTaskStart = emp.tasks[0]?.start; const lastTaskEnd = emp.tasks[emp.tasks.length - 1]?.end; let totalWorkspan = 0;
                    if (firstTaskStart && lastTaskEnd) { totalWorkspan = (lastTaskEnd.getTime() - firstTaskStart.getTime()) / 1000; }
                    const totalIdleTime = Math.max(0, totalWorkspan - emp.totalActiveDuration); const avgBreakTime = emp.taskCount > 1 ? totalIdleTime / (emp.taskCount - 1) : 0;
                    const efficiency = emp.totalActiveDuration > 0 ? (emp.totalItems / (emp.totalActiveDuration / 3600)) : 0;
                    return { name: emp.name, totalItems: emp.totalItems, taskCount: emp.taskCount, totalActiveDuration: emp.totalActiveDuration, avgBreakTime: avgBreakTime, efficiency: efficiency, };
                });
            }
            async function updateDisplay() { const tableBody = document.getElementById('employeePerformanceTableBody'); try { const employees = await fetchWarehousePerformanceData(); tableBody.innerHTML = ''; if (employees.length === 0) { tableBody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-500">Nincs mai adat a munkatársak teljesítményéről.</td></tr>`; return; }
                employees.sort((a, b) => b.totalItems - a.totalItems);
                employees.forEach(emp => { const row = document.createElement('tr'); row.className = "hover:bg-gray-50";
                    row.innerHTML = `<td class="py-3 px-4 font-medium text-gray-900">${emp.name}</td><td class="py-3 px-4 text-right">${emp.totalItems.toLocaleString('hu-HU')}</td><td class="py-3 px-4 text-right">${emp.taskCount.toLocaleString('hu-HU')}</td><td class="py-3 px-4 text-right">${formatSeconds(emp.totalActiveDuration)}</td><td class="py-3 px-4 text-right">${formatSeconds(emp.avgBreakTime)}</td><td class="py-3 px-4 text-right font-semibold text-blue-600">${emp.efficiency.toFixed(1)}</td>`;
                    tableBody.appendChild(row); });
            } catch (error) { console.error("Hiba a 'Munkatársak' adatok frissítésekor:", error); tableBody.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-red-500">Hiba történt az adatok betöltése közben.</td></tr>`; } }
            updateDisplay(); setInterval(updateDisplay, REFRESH_INTERVAL);
        })();
    });
</script>