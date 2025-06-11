<?php // page_raktar_kiszedesek.php ?>
<div class="space-y-8">
    <div class="bg-white p-4 rounded-xl shadow-md mb-6 flex flex-wrap items-center justify-center gap-2 md:gap-4 sticky top-0 z-20"><div class="font-semibold text-slate-700">Időszak:</div><div id="date-filter-buttons" class="flex flex-wrap items-center justify-center gap-1"><button data-period="today" class="date-filter-btn bg-blue-500 text-white px-3 py-1.5 text-sm rounded-full shadow-sm">Ma</button><button data-period="yesterday" class="date-filter-btn bg-slate-200 text-slate-700 hover:bg-slate-300 px-3 py-1.5 text-sm rounded-full">Tegnap</button><button data-period="this_week" class="date-filter-btn bg-slate-200 text-slate-700 hover:bg-slate-300 px-3 py-1.5 text-sm rounded-full">Ez a hét</button><button data-period="this_month" class="date-filter-btn bg-slate-200 text-slate-700 hover:bg-slate-300 px-3 py-1.5 text-sm rounded-full">Ez a hónap</button></div><div class="relative"><input type="text" id="customDateRangePicker" placeholder="Egyedi időszak választása..." class="border border-slate-300 rounded-full px-4 py-1.5 text-sm text-center w-64 cursor-pointer"></div></div>
    <div id="loading-overlay" class="text-center py-10"><div class="text-slate-500 text-lg">Adatok betöltése...</div></div>
    <div id="error-container" class="hidden text-center text-red-500 p-8 bg-white rounded-lg shadow-lg"></div>
    <div id="dashboard-content" class="space-y-8 hidden">
        <section><h2 class="text-2xl font-bold text-slate-700 mb-4">Kivételezések Összesített Teljesítménye</h2><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-semibold text-gray-800 mb-3">Mennyiségi Összesítő</h3><div class="space-y-3"><div class="flex justify-between items-baseline"><span class="text-gray-600">Műveletek:</span><span id="sum_task_count" class="text-2xl font-bold text-gray-900">0</span></div><div class="flex justify-between items-baseline"><span class="text-gray-600">Kiszedések (rendelés):</span><span id="sum_pick_count" class="text-2xl font-bold text-gray-900">0</span></div><div class="flex justify-between items-baseline"><span class="text-gray-600">Termékek (db):</span><span id="sum_item_count" class="text-2xl font-bold text-gray-900">0</span></div></div></div>
            <div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-semibold text-gray-800 mb-3">Teljesítési Pontosság</h3><div class="space-y-1"><div class="flex justify-between items-baseline"><span class="text-gray-600">Tény vs. Terv (db):</span><div><span id="acc_actual_qty" class="text-2xl font-bold text-gray-900">0</span><span class="text-gray-500"> / </span><span id="acc_planned_qty" class="text-xl text-gray-500">0</span></div></div><div class="w-full bg-slate-200 rounded-full h-3 my-2"><div id="acc_bar" class="h-full rounded-full transition-all duration-700 bg-teal-600" style="width: 0%;"></div></div><div class="text-right text-2xl font-bold text-teal-700"><span id="acc_percent">0</span>%</div></div></div>
            <div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-semibold text-gray-800 mb-3">Hatékonysági Átlagok</h3><div class="space-y-3"><div class="flex justify-between items-baseline"><span class="text-gray-600">Átl. Idő / Művelet:</span><span id="eff_avg_time_per_op" class="text-2xl font-bold text-gray-900">00:00:00</span></div><div class="flex justify-between items-baseline"><span class="text-gray-600">Átl. Idő / Kiszedés:</span><span id="eff_avg_time_per_pick" class="text-2xl font-bold text-gray-900">00:00:00</span></div><div class="flex justify-between items-baseline"><span class="text-gray-600">Átl. Termék / Kiszedés:</span><span id="eff_avg_items_per_pick" class="text-2xl font-bold text-gray-900">0.0</span></div><div class="flex justify-between items-baseline"><span class="text-gray-600">Átl. Szünet Kiszedések Közt:</span><span id="eff_avg_downtime" class="text-2xl font-bold text-gray-900">00:00:00</span></div></div></div>
        </div></section>
        <section><h2 class="text-2xl font-bold text-slate-700 mb-4 mt-6">Vizuális Elemzések</h2><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-semibold text-gray-800 mb-3">Óránkénti Teljesítmény (Termékek)</h3><div class="relative" style="height: 300px;"><canvas id="hourlyPerformanceChart"></canvas><div id="hourlyPerformanceChart_message" class="hidden absolute inset-0 flex items-center justify-center text-slate-500"></div></div></div><div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-semibold text-gray-800 mb-3">Műveleti Idők Eloszlása</h3><div class="relative" style="height: 300px;"><canvas id="durationHistogramChart"></canvas><div id="durationHistogramChart_message" class="hidden absolute inset-0 flex items-center justify-center text-slate-500"></div></div></div></div></section>
    </div>
</div>
<script src="https://npmcdn.com/flatpickr/dist/l10n/hu.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            const API_URL = 'api/get_kiszedesek.php';
            const REFRESH_INTERVAL = 5 * 60 * 1000;
            let chartInstances = {};
            let currentIntervalId = null;
            let currentStartDate, currentEndDate;

            const dateFilterButtons = document.getElementById('date-filter-buttons');
            const dashboardContent = document.getElementById('dashboard-content');
            const loadingOverlay = document.getElementById('loading-overlay');
            const errorContainer = document.getElementById('error-container');

            const formatSeconds = (s) => { const seconds=Number(s); if(isNaN(seconds)||seconds<0)return"00:00:00"; return new Date(seconds*1000).toISOString().slice(11,19);};
            const getLocalDateString = (d) => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');

            async function updateDashboardUI(startDate, endDate) {
                dashboardContent.classList.add('hidden');
                errorContainer.classList.add('hidden');
                loadingOverlay.classList.remove('hidden');
                try {
                    const response = await fetch(`${API_URL}?start_date=${getLocalDateString(startDate)}&end_date=${getLocalDateString(endDate)}`);
                    const data = await response.json();
                    if (!data.success) { throw new Error(data.message || 'Ismeretlen API hiba.'); }
                    
                    const { overall, charts } = data;
                    
                    if (overall.isEmpty) {
                        // "Nincs adat" állapot kezelése: 0-kat írunk ki és üres diagramokat mutatunk.
                        document.getElementById('sum_task_count').textContent = '0';
                        document.getElementById('sum_pick_count').textContent = '0';
                        document.getElementById('sum_item_count').textContent = '0';
                        document.getElementById('acc_actual_qty').textContent = '0';
                        document.getElementById('acc_planned_qty').textContent = '0';
                        document.getElementById('acc_percent').textContent = 'N/A';
                        document.getElementById('acc_bar').style.width = `0%`;
                        document.getElementById('eff_avg_time_per_op').textContent = formatSeconds(0);
                        document.getElementById('eff_avg_time_per_pick').textContent = formatSeconds(0);
                        document.getElementById('eff_avg_items_per_pick').textContent = '0.0';
                        document.getElementById('eff_avg_downtime').textContent = formatSeconds(0);
                        updateHourlyChart(null, startDate);
                        updateDurationHistogram(null);
                        return;
                    }

                    document.getElementById('sum_task_count').textContent = parseInt(overall.taskCount).toLocaleString('hu-HU');
                    document.getElementById('sum_pick_count').textContent = parseInt(overall.totalPicks).toLocaleString('hu-HU');
                    document.getElementById('sum_item_count').textContent = parseInt(overall.totalActualItems).toLocaleString('hu-HU');
                    
                    document.getElementById('acc_actual_qty').textContent = parseInt(overall.totalActualItems).toLocaleString('hu-HU');
                    document.getElementById('acc_planned_qty').textContent = parseInt(overall.totalPlannedItems).toLocaleString('hu-HU');
                    document.getElementById('acc_percent').textContent = parseFloat(overall.accuracy).toFixed(1);
                    document.getElementById('acc_bar').style.width = `${Math.min(100, overall.accuracy).toFixed(2)}%`;
                    
                    document.getElementById('eff_avg_time_per_op').textContent = formatSeconds(overall.avgTimePerOp);
                    document.getElementById('eff_avg_time_per_pick').textContent = formatSeconds(overall.avgTimePerPick);
                    document.getElementById('eff_avg_items_per_pick').textContent = parseFloat(overall.avgItemsPerPick).toFixed(1);
                    document.getElementById('eff_avg_downtime').textContent = formatSeconds(overall.avgDowntime);
                    
                    updateHourlyChart(charts.hourly, startDate);
                    updateDurationHistogram(charts.duration);

                } catch (error) {
                    errorContainer.textContent = error.message;
                    errorContainer.classList.remove('hidden');
                } finally {
                    loadingOverlay.classList.add('hidden');
                    if (errorContainer.classList.contains('hidden')) {
                        dashboardContent.classList.remove('hidden');
                    } else {
                        dashboardContent.classList.add('hidden');
                    }
                }
            }
            
            function renderChart(canvasId, config) { if (chartInstances[canvasId]) chartInstances[canvasId].destroy(); const ctx=document.getElementById(canvasId)?.getContext('2d'); if(ctx) { chartInstances[canvasId]=new Chart(ctx,config); } else { console.error("Canvas elem nem található: " + canvasId); } }
            function updateChartOrShowMessage(canvasId, messageId, data, renderFn, noDataMessage = "Nincs adat a diagramhoz.") { const canvas = document.getElementById(canvasId); const messageEl = document.getElementById(messageId); if (!canvas || !messageEl) return; const isDataEmpty = !data || (Array.isArray(data) && data.every(v => v === 0)) || (typeof data === 'object' && Object.values(data).every(val => val == 0)); if (isDataEmpty) { canvas.classList.add('hidden'); messageEl.textContent = noDataMessage; messageEl.classList.remove('hidden'); if(chartInstances[canvasId]) { chartInstances[canvasId].destroy(); delete chartInstances[canvasId];} } else { canvas.classList.remove('hidden'); messageEl.classList.add('hidden'); renderFn(); }}
            function updateHourlyChart(hourlyData, startDate) { updateChartOrShowMessage('hourlyPerformanceChart', 'hourlyPerformanceChart_message', hourlyData, () => { const now=new Date(); const getLocalDateString=(d)=>d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); const dayOfWeek=startDate.getDay(); const worktimeEndHour=(dayOfWeek >= 1 && dayOfWeek <= 4) ? 15 : 13; const labels=Array.from({length:13},(_,i)=>`${String(i+7).padStart(2,'0')}:00`); const data=hourlyData.slice(7,20); const colors=labels.map((l,i)=>{const h=i+7;if(getLocalDateString(startDate)===getLocalDateString(now)&&h===now.getHours())return'#22C55E';if(dayOfWeek===0||dayOfWeek===6)return'#b688ff';if(h<7)return'#cbd5e1';if(dayOfWeek===5){if(h<13||(h===13&&now.getMinutes()<30))return'#502785';}else{if(h<15||(h===15&&now.getMinutes()<30))return'#502785';}return'#B688FF';}); renderChart('hourlyPerformanceChart',{type:'bar',data:{labels,datasets:[{label:'Termékek (db)',data,backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{ticks:{precision:0}}}}});});}
            function updateDurationHistogram(durationData) { updateChartOrShowMessage('durationHistogramChart', 'durationHistogramChart_message', durationData, () => { const data = {'0-60s':durationData['d_0_60s'],'1-5p':durationData['d_1_5m'],'5-15p':durationData['d_5_15m'],'15p+':durationData['d_15p_plus']}; renderChart('durationHistogramChart',{type:'bar',data:{labels:Object.keys(data),datasets:[{label:'Műveletek száma',data:Object.values(data),backgroundColor:'#8568AA'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});});}

            let fp; function handleDateSelection(start, end) { currentStartDate = start; currentEndDate = end; updateDashboardUI(start, end); }
            function handlePeriodChange(period) { const now=new Date(); let start=new Date(now); let end=new Date(now); start.setHours(0,0,0,0); end.setHours(23,59,59,999); if(period==='yesterday'){start.setDate(start.getDate()-1);end.setDate(end.getDate()-1);} else if(period==='this_week'){const day = start.getDay(); const diff = start.getDate() - day + (day == 0 ? -6:1); start.setDate(diff);} else if(period==='this_month'){start.setDate(1);} fp.setDate([start, end], false); handleDateSelection(start, end); }
            dateFilterButtons.addEventListener('click',(e)=>{ if(e.target.classList.contains('date-filter-btn')){ dateFilterButtons.querySelectorAll('.date-filter-btn').forEach(b=>{b.classList.remove('bg-blue-500','text-white');b.classList.add('bg-slate-200','text-slate-700');}); e.target.classList.add('bg-blue-500','text-white'); e.target.classList.remove('bg-slate-200','text-slate-700'); handlePeriodChange(e.target.dataset.period);}});
            fp = flatpickr("#customDateRangePicker",{ mode:"range", dateFormat:"Y-m-d", locale:"hu", onClose: function(selectedDates) { if (selectedDates.length === 2) { dateFilterButtons.querySelectorAll('.date-filter-btn').forEach(b=>{b.classList.remove('bg-blue-500','text-white');b.classList.add('bg-slate-200','text-slate-700');}); const end=selectedDates[1]; end.setHours(23,59,59,999); handleDateSelection(selectedDates[0], end);}}});
            
            async function run() {
                handlePeriodChange('today');
                if (currentIntervalId) clearInterval(currentIntervalId);
                currentIntervalId = setInterval(() => { if (currentStartDate && currentEndDate) { updateDashboardUI(currentStartDate, currentEndDate); } }, REFRESH_INTERVAL);
            }
            run();
        })();
    });
</script>