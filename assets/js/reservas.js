let reservasPagina = [],
    paginacionReservas = { page: 1, per_page: 10, total: 0, total_pages: 1 },
    paginaActualRes = 1;
const registrosPorPaginaRes = 10;

document.addEventListener('DOMContentLoaded', function() {
    cargarRifasSelectRes();
    cargarReservas();

    const inputSearch = document.getElementById('searchReservas');
    if (inputSearch) inputSearch.addEventListener('input', debounce(() => { paginaActualRes = 1; cargarReservas(); }, 600));

    const selPeriodo = document.getElementById('filterPeriodoRes');
    if (selPeriodo) {
        selPeriodo.addEventListener('change', function() {
            if (this.value !== "") {
                document.getElementById('fecha_inicio_res').value = '';
                document.getElementById('fecha_fin_res').value = '';
            }
            paginaActualRes = 1;
            cargarReservas();
        });
    }

    ['fecha_inicio_res','fecha_fin_res'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', function() {
            if (this.value !== "") document.getElementById('filterPeriodoRes').value = '';
            if (document.getElementById('fecha_inicio_res').value && document.getElementById('fecha_fin_res').value) {
                paginaActualRes = 1; cargarReservas();
            }
        });
    });

    const selRifa = document.getElementById('filterRifaRes');
    if (selRifa) selRifa.addEventListener('change', () => { paginaActualRes = 1; cargarReservas(); });

    const selEstado = document.getElementById('filterEstadoRes');
    if (selEstado) selEstado.addEventListener('change', () => { paginaActualRes = 1; cargarReservas(); });
});

async function cargarRifasSelectRes() {
    try {
        const data = await API.post('rifas', { action: 'obtener_rifas' });
        if (data.success && data.data) {
            const select = document.getElementById('filterRifaRes');
            select.innerHTML = '<option value="">Todas las rifas</option>';
            const lista = Array.isArray(data.data) ? data.data : [data.data];
            lista.forEach(r => select.innerHTML += `<option value="${r.id_raffle}">${r.title_raffle}</option>`);
        }
    } catch(e) { console.error(e); }
}

async function cargarReservas() {
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const fd = new FormData();
        fd.append('action', 'obtener');
        fd.append('search', document.getElementById('searchReservas')?.value || '');
        fd.append('periodo', document.getElementById('filterPeriodoRes')?.value || '');
        fd.append('id_raffle', document.getElementById('filterRifaRes')?.value || '');
        fd.append('fecha_inicio', document.getElementById('fecha_inicio_res')?.value || '');
        fd.append('fecha_fin', document.getElementById('fecha_fin_res')?.value || '');
        fd.append('status', document.getElementById('filterEstadoRes')?.value || 'pendientes');
        fd.append('page', paginaActualRes);
        fd.append('per_page', registrosPorPaginaRes);

        const data = await API.post('reservations', fd);

        if (data.success) {
            reservasPagina = Array.isArray(data.data) ? data.data : (data.data ? [data.data] : []);
            if (data.pagination) {
                paginacionReservas = data.pagination;
                paginaActualRes = data.pagination.page;
            }
        } else {
            reservasPagina = [];
            paginacionReservas = { page: 1, per_page: registrosPorPaginaRes, total: 0, total_pages: 1 };
        }

        renderPaginaReservas();
    } catch(e) { console.error(e); }
    finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function renderPaginaReservas() {
    renderTablaReservas(reservasPagina);

    if (typeof PaginationHelper !== 'undefined') {
        PaginationHelper.render({
            totalItems: paginacionReservas.total,
            currentPage: paginaActualRes,
            limit: registrosPorPaginaRes,
            containerId: 'contenedorPaginacionRes',
            infoId: 'infoPaginacionRes',
            callbackName: 'cambiarPaginaRes'
        });
    }
}

function cambiarPaginaRes(p) {
    const totalPaginas = paginacionReservas.total_pages || 1;
    paginaActualRes = Math.max(1, Math.min(p, totalPaginas));
    cargarReservas();
}

function renderTablaReservas(rows) {
    const tbody = document.getElementById('bodyTablaReservas');
    if (!tbody) return;
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">No se encontraron registros</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map(r => {
        const cantidad = r.quantity ?? (r.tickets?.length || 0);
        const total = Number(r.total ?? (cantidad * Number(r.price_raffle || 0)));
        const inicial = (r.name_customer || 'C').charAt(0).toUpperCase();
        const rifaTitle = r.title_raffle || '—';
        const tickets = Array.isArray(r.tickets) ? r.tickets : [];
        const numerosHTML = renderAdminNumBadges(tickets, 12);

        const telefonoLimpio = (r.phone_customer || '').replace(/\D/g, '');
        const whatsappUrl = telefonoLimpio
            ? `https://api.whatsapp.com/send?phone=57${telefonoLimpio}`
            : '#';

        const f = new Date(r.date_created_reservation ?? r.date_created ?? Date.now());
        const fecha = f.toLocaleDateString('es-CO');
        const hora = f.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });

        const estadoTxt = r.status_reservation == 1 ? 'Reservada' : (r.status_reservation == 2 ? 'Cancelada' : 'Completada');
        const estadoClass = r.status_reservation == 1 ? 'bg-warning-subtle text-warning' : (r.status_reservation == 2 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success');

        const esActiva = r.status_reservation == 1;
        const rowClass = esActiva ? 'card-row-reserva' : 'opacity-50';

        const btnAceptarDesktop = esActiva
            ? `<button class="btn btn-outline-success" title="Aceptar venta" onclick="aceptarVentaReserva(${r.id_reservation})"><i class="ti ti-check"></i></button>
               <button class="btn btn-outline-danger" title="Rechazar reserva" onclick="cancelarReserva(${r.id_reservation})"><i class="ti ti-x"></i></button>`
            : '';
        const btnAceptarMobile = esActiva
            ? `<button type="button" class="btn btn-outline-success flex-fill" onclick="aceptarVentaReserva(${r.id_reservation})" title="Aceptar venta"><i class="ti ti-check"></i> Aceptar</button>
               <button type="button" class="btn btn-outline-danger flex-fill" onclick="cancelarReserva(${r.id_reservation})" title="Rechazar"><i class="ti ti-x"></i></button>`
            : '';

        const mobileHead = renderAdminMobileCardHead({
            inicial,
            name: cellTruncate(r.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(r.phone_customer, whatsappUrl),
            statusHtml: `<span class="badge ${estadoClass} border px-2 py-1 rounded-pill">${estadoTxt}</span>`,
            metaHtml: renderCardMetaChips(cantidad, total),
            rifaHtml: cellRifaName(rifaTitle),
            numbersHtml: numerosHTML,
            codeHtml: renderAdminCodeChip(r.token_reservation),
        });

        const desktopClient = renderAdminDesktopClient({
            inicial,
            name: cellTruncate(r.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(r.phone_customer, whatsappUrl),
        });

        return `
        <tr class="align-middle border-bottom ${rowClass}">
            <td class="d-none mobile-hide">${r.id_reservation}</td>

            <td class="py-3 ps-3 mobile-card-head" data-label="">
                ${mobileHead}
                ${desktopClient}
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Código">
                <span class="token-chip">${r.token_reservation}</span>
            </td>

            <td class="py-3 col-rifa mobile-field-desktop-only" data-label="Rifa">
                ${renderRifaColumnDesktop(cantidad, rifaTitle, tickets)}
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Total">
                <span class="fw-bold text-dark">$${total.toLocaleString('es-CO')}</span>
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Estado">
                <span class="badge ${estadoClass} border px-3 py-2 rounded-pill">${estadoTxt}</span>
            </td>

            <td class="py-3" data-label="Fecha">
                <div class="d-flex flex-column text-muted">
                    <span class="text-dark fw-medium">${fecha}</span>
                    <span style="font-size:0.85rem;">${hora}</span>
                </div>
            </td>

            <td class="py-3 text-end pe-3 mobile-card-actions" data-label="">
                ${esActiva ? `
                <div class="d-lg-none">
                    ${renderAdminMobileActions(btnAceptarMobile)}
                </div>
                <div class="d-none d-lg-block">
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        ${btnAceptarDesktop}
                    </div>
                </div>` : ''}
            </td>
        </tr>`;
    }).join('');
}

function limpiarFiltrosReservas() {
    document.getElementById('searchReservas').value = '';
    document.getElementById('filterPeriodoRes').value = '';
    document.getElementById('filterEstadoRes').value = 'pendientes';
    document.getElementById('filterRifaRes').value = '';
    document.getElementById('fecha_inicio_res').value = '';
    document.getElementById('fecha_fin_res').value = '';
    paginaActualRes = 1;
    cargarReservas();
}

function debounce(f, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), w); }; }

function aceptarVentaReserva(idReservation) {
    confirmarAccion({
        titulo: 'Confirmar venta',
        html: '<p class="text-muted mb-0">La reserva pasará a venta confirmada y los números quedarán registrados como vendidos.</p>',
        textoConfirmar: 'Confirmar venta',
        tipoConfirmar: 'success',
        onConfirm: async () => {
            const fd = new FormData();
            fd.append('action', 'aceptar_venta');
            fd.append('id_reservation', idReservation);

            const data = await API.post('reservations', fd);

            if (!data?.success) {
                alertify.error(data.message || 'Error al aceptar la venta');
                throw new Error(data.message || 'accept_failed');
            }

            cargarReservas();

            if (data.html_recibo) {
                document.getElementById('cuerpoReciboRes').innerHTML = data.html_recibo;
                new bootstrap.Modal(document.getElementById('modalReciboRes')).show();
            }
        },
    });
}


function cancelarReserva(idReservation) {
    confirmarAccion({
        titulo: 'Rechazar reserva',
        mensaje: 'Se eliminará la reserva y los números quedarán disponibles de nuevo. ¿Continuar?',
        onConfirm: async () => {
            const fd = new FormData();
            fd.append('action', 'cancelar');
            fd.append('id_reservation', idReservation);

            const data = await API.post('reservations', fd);

            if (data.success) {
                alertify.success('Reserva eliminada');
                cargarReservas();
            } else {
                alertify.error(data.message || 'Error al eliminar la reserva');
            }
        }
    });
}




function liberarReservasMasivo() {
    confirmarAccion({
        titulo: 'Liberar reservas',
        mensaje: 'Esto cancelará TODAS las reservas activas y liberará los números. ¿Deseas continuar?',
        onConfirm: () => ejecutarLiberacion()
    });
}

async function ejecutarLiberacion() {
    const fd = new FormData();
    fd.append('action', 'liberar_reservas_masivo');

    const j = await API.post('reservations', fd);

    if (j.success) {
        alertify.success('Reservas liberadas correctamente');
        cargarReservas();
    } else {        
        alertify.error(j.message || 'Error al liberar reservas');
    }
}
