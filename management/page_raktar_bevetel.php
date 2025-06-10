<?php // page_raktar_bevetel.php ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <header class="mb-4"><h2 class="text-xl font-semibold text-slate-800 text-center">Mai Bevételezési Adatok</h2></header>
        <main>
            <div id="bevetel_loader" class="text-center py-8 text-slate-500">Adatok betöltése...</div>
            <div id="bevetel_data" class="hidden">
                <div class="text-center mb-4"><span id="received_goods_quantity" class="text-5xl font-bold text-slate-900">---</span><span class="text-lg text-slate-600 align-baseline"> féle termék</span></div>
                <div class="text-center border-t border-slate-200 pt-4"><h3 class="text-md font-medium text-slate-600 mb-1">Bevételezett beszállítók:</h3><p id="received_goods_suppliers" class="text-sm text-slate-700 min-h-[2.0em] px-2 break-words leading-relaxed">...</p></div>
            </div>
        </main>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        (function() {
            // A teljes JavaScript kód, amit az előző válaszban adtam, ide jön...
            const RECEIVED_GOODS_DATA_URL = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9JNGprVzBkd0hPSG5kekFqTEJOcWdxcEZzaENGNVVBWGNUalc0R003eHZFakhFTDBud2syWS1SY1hPd05nRlpVX3d4RzVkeFJBR1VfXzRqSkJNb25Lb3hIaTNzSE9Qay1aRk5EZUFObTFKWGFBejg9';
            const REFRESH_INTERVAL = 5 * 60 * 1000;
            async function fetchReceivedGoodsData() { const response = await fetch(`${RECEIVED_GOODS_DATA_URL}&timestamp=${new Date().getTime()}`); if (!response.ok) throw new Error(`Adatforrás hiba (${response.status})`); const csvText = await response.text(); const lines = csvText.trim().split('\n'); if (lines.length === 0 || (lines.length === 1 && lines[0].trim() === '')) return { totalQuantity: 0, suppliers: [] }; const totalQuantity = Math.max(0, lines.length - 1); const suppliers = new Set(); const colJ_Index = 9; const startIndex = (lines.length > 0 && lines[0].includes(',')) ? 1 : 0;
                for (let i = startIndex; i < lines.length; i++) { const line = lines[i].trim(); if (line === '') continue; const values = line.split(','); if (values.length > colJ_Index && values[colJ_Index]?.trim()) { suppliers.add(values[colJ_Index].trim()); } }
                return { totalQuantity, suppliers: Array.from(suppliers) };
            }
            async function updateDisplay() { const loader = document.getElementById('bevetel_loader'); const dataContainer = document.getElementById('bevetel_data');
                try {
                    const data = await fetchReceivedGoodsData();
                    document.getElementById('received_goods_quantity').textContent = data.totalQuantity.toLocaleString('hu-HU');
                    const suppliersEl = document.getElementById('received_goods_suppliers');
                    if (data.suppliers.length > 0) { suppliersEl.textContent = data.suppliers.join(', '); } else { suppliersEl.textContent = 'Nincs mai beszállító.'; }
                    loader.classList.add('hidden'); dataContainer.classList.remove('hidden');
                } catch (error) { console.error("Hiba a 'Bevételezés' adatok frissítésekor:", error); loader.textContent = "Hiba történt az adatok betöltése közben."; loader.classList.add('text-red-500'); }
            }
            updateDisplay(); setInterval(updateDisplay, REFRESH_INTERVAL);
        })();
    });
</script>