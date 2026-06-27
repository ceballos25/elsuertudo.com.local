/**
 * dashboard.js - Versión Final Nivel Dios
 */
let chartTendenciaInst = null;
let chartMediosDineroInst = null;
let chartMediosCantInst = null;
let chartMediosTransInst = null;
let chartTopCliInst = null;
let chartHeatmapInst = null; // Nuevo
let chartPaquetesInst = null; // Nuevo

document.addEventListener('DOMContentLoaded', async () => {
    await cargarRifas();
    cambiarPeriodo();

    document.getElementById('filterPeriodo')?.addEventListener('change', cambiarPeriodo);

    document.getElementById('filterRifa')?.addEventListener('change', cargarDashboard);

    ['filterDesde', 'filterHasta'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', () => {
            const selPeriodo = document.getElementById('filterPeriodo');
            if (selPeriodo) selPeriodo.value = '';
            const desde = document.getElementById('filterDesde')?.value || '';
            const hasta = document.getElementById('filterHasta')?.value || '';
            if (desde && hasta) cargarDashboard();
        });
    });
});

function cambiarPeriodo() {
    const periodo = document.getElementById('filterPeriodo').value;
    const date = new Date();
    const formatDate = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    let desde = '', hasta = '';

    if (periodo === 'hoy') { desde = hasta = formatDate(date); }
    else if (periodo === 'ayer') { date.setDate(date.getDate() - 1); desde = hasta = formatDate(date); }
    else if (periodo === 'semana') { const day = date.getDay() || 7; if(day!==1) date.setHours(-24 * (day-1)); desde = formatDate(date); hasta = formatDate(new Date()); }
    else if (periodo === 'mes') { desde = formatDate(new Date(date.getFullYear(), date.getMonth(), 1)); hasta = formatDate(new Date(date.getFullYear(), date.getMonth() + 1, 0)); }
    else if (periodo === 'ano') { desde = formatDate(new Date(date.getFullYear(), 0, 1)); hasta = formatDate(new Date(date.getFullYear(), 11, 31)); }

    if (desde && hasta) {
        document.getElementById('filterDesde').value = desde;
        document.getElementById('filterHasta').value = hasta;
        cargarDashboard();
    }
}

async function cargarRifas() {
    try {
        const j = await API.post('rifas', { action: 'obtener_rifas' });
        const sel = document.getElementById('filterRifa');
        if (j.success && sel) {
            sel.innerHTML = '<option value="">🌐 Todas las rifas activas</option>';
            j.data.forEach(x => sel.innerHTML += `<option value="${x.id_raffle}">${x.title_raffle}</option>`);
            if (j.data.length > 0) {
                sel.value = String(j.data[0].id_raffle);
            }
        }
    } catch (e) { console.error(e); }
}

async function cargarDashboard() {
    const desde = document.getElementById('filterDesde').value;
    const hasta = document.getElementById('filterHasta').value;
    const rifa  = document.getElementById('filterRifa').value;
    const content = document.getElementById('dashContent');

    content?.classList.add('dash-loading');
    updateFilterResumen();

    try {
        const data = await API.post('dashboard', {
            action: 'obtener_dashboard',
            fechaDesde: desde,
            fechaHasta: hasta,
            id_raffle: rifa,
        });

        if (data.success) {
            renderKPIs(data.data.kpis);
            renderCharts(data.data.graficas);
            renderTabla(data.data.ultimasVentas);
        }
    } catch (e) { console.error(e); }
    finally { content?.classList.remove('dash-loading'); }
}

function limpiarFiltrosDashboard() {
    const sel = document.getElementById('filterRifa');
    if (sel) {
        const primeraActiva = [...sel.options].find(o => o.value);
        sel.value = primeraActiva ? primeraActiva.value : '';
    }
    document.getElementById('filterPeriodo').value = 'mes';
    cambiarPeriodo();
}

function updateFilterResumen() {
    const el = document.getElementById('dashFilterResumen');
    if (!el) return;

    const rifaSel = document.getElementById('filterRifa');
    const rifaText = rifaSel?.options[rifaSel.selectedIndex]?.text?.replace(/^🌐\s*/, '') || 'Todas las rifas activas';
    const desde = document.getElementById('filterDesde')?.value || '';
    const hasta = document.getElementById('filterHasta')?.value || '';

    const fmt = (f) => {
        if (!f) return '';
        const [y, m, d] = f.split('-');
        return `${d}/${m}/${y}`;
    };

    const rango = (desde && hasta) ? `${fmt(desde)} – ${fmt(hasta)}` : 'Sin rango';
    el.textContent = `${rifaText} · ${rango}`;
}

function pct(part, total) {
    if (!total) return 0;
    return Math.round((part / total) * 100);
}

function setBar(id, value) {
    const el = document.getElementById(id);
    if (el) el.style.width = `${value}%`;
}

function renderKPIs(kpis) {
    const fmtMoney = (n) => '$' + Number(n).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    const fmtNum = (n) => Number(n).toLocaleString('es-CO');
    const total = kpis.totalNumeros || 0;

    document.getElementById('kpiVentas').innerText = fmtMoney(kpis.totalVentas);
    document.getElementById('kpiVentasMeta').innerText = `${fmtNum(kpis.totalTransacciones)} transacciones en el periodo`;
    document.getElementById('kpiVendidos').innerText = fmtNum(kpis.numerosVendidos);
    document.getElementById('kpiReservados').innerText = fmtNum(kpis.numerosReservados);
    document.getElementById('kpiDisponibles').innerText = fmtNum(kpis.numerosDisponibles);
    document.getElementById('kpiClientes').innerText = fmtNum(kpis.totalClientes);

    const pV = pct(kpis.numerosVendidos, total);
    const pR = pct(kpis.numerosReservados, total);
    const pD = pct(kpis.numerosDisponibles, total);

    document.getElementById('kpiVendidosMeta').innerText = `${pV}% del total`;
    document.getElementById('kpiReservadosMeta').innerText = `${pR}% del total`;
    document.getElementById('kpiDisponiblesMeta').innerText = `${pD}% del total`;

    setBar('kpiVendidosBar', pV);
    setBar('kpiReservadosBar', pR);
    setBar('kpiDisponiblesBar', pD);

    document.getElementById('kpiTotalNumeros').innerText = `${fmtNum(total)} números en total`;
    document.getElementById('legendVendidos').innerText = fmtNum(kpis.numerosVendidos);
    document.getElementById('legendReservados').innerText = fmtNum(kpis.numerosReservados);
    document.getElementById('legendDisponibles').innerText = fmtNum(kpis.numerosDisponibles);

    setBar('stackVendidos', pV);
    setBar('stackReservados', pR);
    setBar('stackDisponibles', pD);
}

// Configuración Base Donut
const commonDonutOptions = {
    chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
    legend: { position: 'bottom' },
    plotOptions: { pie: { donut: { size: '70%', labels: { show: true, name: { show: true, fontSize: '14px' }, value: { show: true, fontSize: '22px', fontWeight: 700, offsetY: 5 }, total: { show: true, label: 'TOTAL', fontSize: '12px', fontWeight: 600, color: '#6c757d' } } } } },
    dataLabels: { enabled: false }
};

function renderCharts(graficas) {
    const fmtMoney = v => '$' + Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    const fmtNum = v => Number(v).toLocaleString('es-CO');

    // PALETA DE COLORES PROFESIONAL (Armonía Azul/Verde/Violeta)
    const colorsTicket = ['#4361ee', '#3a0ca3', '#7209b7', '#f72585']; // Gama Violeta/Rosa fuerte
    const colorsDinero = ['#2ec4b6', '#ff9f1c', '#e71d36', '#011627']; // Gama Contraste (Verde/Naranja)
    const colorsTrans  = ['#3f37c9', '#4cc9f0', '#4895ef', '#560bad']; // Gama Azul Profundo

    // 1. TENDENCIA
    const fechas = graficas.tendencia.map(x => {
        const [y, m, d] = x.fecha.split('-');
        return `${d}/${m}`;
    });
    const optTendencia = {
        series: [{ name: 'Ventas ($)', data: graficas.tendencia.map(x => x.total) }],
        chart: { type: 'area', height: 350, toolbar: { show: false }, fontFamily: 'inherit', animations: { enabled: true, speed: 600 } },
        xaxis: { categories: fechas, labels: { rotate: -45, style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: (val) => fmtMoney(val) } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#4361ee'],
        tooltip: { y: { formatter: (val) => fmtMoney(val) } },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05 } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        noData: { text: 'Sin ventas en este periodo', align: 'center', verticalAlign: 'middle' },
    };
    if(chartTendenciaInst) chartTendenciaInst.destroy();
    chartTendenciaInst = new ApexCharts(document.querySelector("#chartTendencia"), optTendencia);
    chartTendenciaInst.render();

    // 2. DONUT 1: VENTAS (TRANSACCIONES)
    const optTrans = JSON.parse(JSON.stringify(commonDonutOptions));
    optTrans.series = graficas.mediosPagoTransacciones.length ? graficas.mediosPagoTransacciones : [1];
    optTrans.labels = graficas.mediosPagoLabels.length ? graficas.mediosPagoLabels : ['Sin datos'];
    optTrans.colors = colorsTrans;
    if (!graficas.mediosPagoTransacciones.length) optTrans.colors = ['#e2e8f0'];
    optTrans.plotOptions.pie.donut.labels.value.formatter = val => fmtNum(val);
    optTrans.plotOptions.pie.donut.labels.total.formatter = w => fmtNum(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
    optTrans.tooltip = { y: { formatter: v => fmtNum(v) + ' Ventas' } };
    
    if(chartMediosTransInst) chartMediosTransInst.destroy();
    chartMediosTransInst = new ApexCharts(document.querySelector("#chartMediosTransacciones"), optTrans);
    chartMediosTransInst.render();

    // 3. DONUT 2: NÚMEROS (TICKETS)
    const optTick = JSON.parse(JSON.stringify(commonDonutOptions));
    optTick.series = graficas.mediosPagoTickets.length ? graficas.mediosPagoTickets : [1];
    optTick.labels = graficas.mediosPagoLabels.length ? graficas.mediosPagoLabels : ['Sin datos'];
    optTick.colors = colorsTicket;
    if (!graficas.mediosPagoTickets.length) optTick.colors = ['#e2e8f0'];
    optTick.plotOptions.pie.donut.labels.value.formatter = val => fmtNum(val);
    optTick.plotOptions.pie.donut.labels.total.formatter = w => fmtNum(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
    optTick.tooltip = { y: { formatter: v => fmtNum(v) + ' Números' } };
    
    if(chartMediosCantInst) chartMediosCantInst.destroy();
    chartMediosCantInst = new ApexCharts(document.querySelector("#chartMediosTickets"), optTick);
    chartMediosCantInst.render();

    // 4. DONUT 3: DINERO ($)
    const optDin = JSON.parse(JSON.stringify(commonDonutOptions));
    optDin.series = graficas.mediosPagoDinero.length ? graficas.mediosPagoDinero : [1];
    optDin.labels = graficas.mediosPagoLabels.length ? graficas.mediosPagoLabels : ['Sin datos'];
    optDin.colors = colorsDinero;
    if (!graficas.mediosPagoDinero.length) optDin.colors = ['#e2e8f0'];
    optDin.plotOptions.pie.donut.labels.value.formatter = val => fmtMoney(val);
    optDin.plotOptions.pie.donut.labels.total.formatter = w => fmtMoney(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
    optDin.tooltip = { y: { formatter: v => fmtMoney(v) } };
    
    if(chartMediosDineroInst) chartMediosDineroInst.destroy();
    chartMediosDineroInst = new ApexCharts(document.querySelector("#chartMediosDinero"), optDin);
    chartMediosDineroInst.render();

    // 5. TOP CLIENTES
    const topClientes = graficas.topClientes.length ? graficas.topClientes : [{ name: 'Sin datos', total: 0, cantidad: 0, telefono: '' }];
    const optTopCli = {
        series: [{ name: 'Compras', data: topClientes.map(x => x.total) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '65%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: topClientes.map(x => x.name), labels: { style: { fontSize: '11px' } } },
        colors: ['#212529'],
        grid: { show: false },
        tooltip: {
            custom: function({ dataPointIndex }) {
                const c = topClientes[dataPointIndex];
                if (!c || c.name === 'Sin datos') return '';
                return `
                <div class="px-3 py-2 border rounded shadow bg-white text-dark text-start"
                    style="font-size: 0.85rem; min-width: 180px;">
                    <div class="fw-bold mb-2 text-primary text-uppercase">${c.name}</div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>💰 Total:</span>
                        <span class="fw-bold">${fmtMoney(c.total)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>🎟️ Números:</span>
                        <span class="fw-bold">${fmtNum(c.cantidad)}</span>
                    </div>
                    <div class="small text-muted">
                        <i class="ti ti-phone me-1"></i> ${c.telefono}
                    </div>
                </div>`;
            }
        }

    };
    if(chartTopCliInst) chartTopCliInst.destroy();
    chartTopCliInst = new ApexCharts(document.querySelector("#chartTopClientes"), optTopCli);
    chartTopCliInst.render();

    // 7. HEATMAP
    const optHeat = {
        series: graficas.heatmap,
        chart: { type: 'heatmap', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
        dataLabels: { enabled: false },
        colors: ["#dd1313"],
        title: { text: '' },
        plotOptions: { heatmap: { shadeIntensity: 0.5, colorScale: { ranges: [{ from: 0, to: 0, color: '#f8f9fa', name: 'Sin Ventas' }] } } },
        tooltip: { y: { formatter: v => v + ' Ventas' } }
    };
    if(chartHeatmapInst) chartHeatmapInst.destroy();
    chartHeatmapInst = new ApexCharts(document.querySelector("#chartHeatmap"), optHeat);
    chartHeatmapInst.render();

    // 8. PAQUETES
    const paquetes = graficas.paquetes.length ? graficas.paquetes : [{ name: 'Sin datos', data: 0 }];
    const optPaq = {
        series: [{ name: 'Ventas', data: paquetes.map(x => x.data) }],
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        dataLabels: { enabled: paquetes[0].name !== 'Sin datos' },
        xaxis: { categories: paquetes.map(x => x.name) },
        colors: ['#10b981'],
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        tooltip: { y: { formatter: v => v + ' veces comprado' } }
    };
    if(chartPaquetesInst) chartPaquetesInst.destroy();
    chartPaquetesInst = new ApexCharts(document.querySelector("#chartPaquetes"), optPaq);
    chartPaquetesInst.render();
}

function renderTabla(ventas) {
    const tbody = document.getElementById('tablaUltimasVentas');
    if (!ventas || ventas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay ventas registradas.</td></tr>';
        return;
    }

    const fmtMoney = v => '$' + Number(v).toLocaleString('es-CO');

    tbody.innerHTML = ventas.map(v => `
        <tr style="font-size: 0.9rem;">
            <td class="ps-4" data-label="Código">
                <span class="font-monospace bg-light border px-2 py-1 rounded small">
                    ${v.code_sale}
                </span>
            </td>
            <td class="fw-bold text-dark text-capitalize mobile-card-head" data-label="">
                ${v.name_customer}
            </td>
            <td class="col-rifa" data-label="Rifa">${cellRifaName(v.title_raffle)}</td>
            <td class="text-success fw-bold" data-label="Total">
                ${fmtMoney(v.total_sale)}
            </td>
            <td class="text-end pe-4 text-muted small" data-label="Fecha">
                ${(() => {
                    const f = new Date(v.date_created_sale.replace(' ', 'T'));
                    f.setHours(f.getHours() - 5);
                    return f.toLocaleDateString('es-CO');
                })()}
            </td>
        </tr>
    `).join('');
}
