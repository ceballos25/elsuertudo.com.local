/**
 * numeros.js
 */
let cache = [], paginaActual = 1;
const registrosPorPagina = 20;

document.addEventListener('DOMContentLoaded', () => {
    cargarRifasSelect();

    const elRifa = document.getElementById('filterRifa');
    const elEstado = document.getElementById('filterEstado');
    const elSearch = document.getElementById('searchNumeros');

    if (elRifa) elRifa.addEventListener('change', () => { paginaActual = 1; cargarInventario(); });
    if (elEstado) elEstado.addEventListener('change', () => { paginaActual = 1; cargarInventario(); });
    if (elSearch) elSearch.addEventListener('input', debounce(() => { paginaActual = 1; cargarInventario(); }, 500));

    const selImagenRifa = document.getElementById('imagenRifaSelectNums');
    if (selImagenRifa) selImagenRifa.addEventListener('change', generarPreviewImagenReservados);
});

async function cargarRifasSelect() {
    try {
        const j = await API.post('rifas', { action: 'obtener_rifas' });
        const s = document.getElementById('filterRifa');
        if (j.success && s) {
            s.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            j.data.forEach(r => {
                s.innerHTML += `<option value="${r.id_raffle}">${r.title_raffle}</option>`;
            });
            if(j.data.length > 0) {
                s.value = j.data[0].id_raffle;
                cargarInventario();
            }
        }
    } catch (e) { console.error(e); }
}

async function cargarInventario() {
    const idRaffle = document.getElementById('filterRifa').value;
    if(!idRaffle) return;

    document.getElementById('bodyTablaNumeros').innerHTML = `<tr><td colspan="3" class="text-center py-5 text-muted">Cargando...</td></tr>`;

    try {
        const fd = new FormData();
        fd.append('action', 'obtener_inventario');
        fd.append('id_raffle', idRaffle);
        fd.append('search', document.getElementById('searchNumeros')?.value || '');
        fd.append('status', document.getElementById('filterEstado')?.value || '');

        const data = await API.post('numeros', fd);
        
        cache = data.success ? data.data : [];
        renderTodo();
    } catch (e) { console.error(e); }
}

function renderTodo() {
    const totalPaginas = Math.max(1, Math.ceil(cache.length / registrosPorPagina));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;

    if (typeof PaginationHelper !== 'undefined') {
        const segmento = PaginationHelper.getSegment(cache, paginaActual, registrosPorPagina);
        renderTabla(segmento);
        PaginationHelper.render({
            totalItems: cache.length, currentPage: paginaActual, limit: registrosPorPagina,
            containerId: 'contenedorPaginacion', infoId: 'infoPaginacion', callbackName: 'cambiarPagina'
        });
    } else {
        const inicio = (paginaActual - 1) * registrosPorPagina;
        const segmento = cache.slice(inicio, inicio + registrosPorPagina);
        renderTabla(segmento);
    }
}

function cambiarPagina(p) {
    const totalPaginas = Math.max(1, Math.ceil(cache.length / registrosPorPagina));
    paginaActual = Math.max(1, Math.min(p, totalPaginas));
    renderTodo();
}

function renderTabla(datos) {
    const tbody = document.getElementById('bodyTablaNumeros');
    
    if (!datos || datos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-muted">No se encontraron números</td></tr>`;
        return;
    }

    tbody.innerHTML = datos.map((t) => {
        const status = parseInt(t.status_ticket); // 0=Libre, 1=Vendido, 2=Reservado
        
        let badge = '';
        let boton = '';

        if (status === 1) {
            // VENDIDO
            badge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3">Vendido</span>';
            boton = `<button class="btn btn-sm btn-light border text-muted" disabled title="No se puede cambiar"><i class="ti ti-ban me-1"></i>Bloqueado</button>`;
        } else if (status === 2) {
            // RESERVADO
            badge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3">Reservado</span>';
            // Botón para Liberar (Volver a 0)
            boton = `<button class="btn btn-sm btn-outline-success border px-3" onclick="cambiarEstadoTicket(${t.id_ticket}, 0)">
                        <i class="ti ti-lock-open me-1"></i>Liberar
                     </button>`;
        } else {
            // DISPONIBLE (0)
            badge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3">Disponible</span>';
            // Botón para Reservar/Bloquear (Volver a 2)
            boton = `<button class="btn btn-sm btn-outline-secondary border px-3" onclick="cambiarEstadoTicket(${t.id_ticket}, 2)">
                        <i class="ti ti-lock me-1"></i>Reservar
                     </button>`;
        }

        let chipClass = 'ticket-num-chip';
        if (status === 1 || status === 2) {
            chipClass += ' ticket-num-chip--sold';
        }

        return `
        <tr class="align-middle border-bottom hover-shadow">
            <td class="py-3 ps-5 text-start mobile-card-head" data-label="">
                <div class="d-flex align-items-center justify-content-between gap-3 w-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="${chipClass}">${t.number_ticket}</div>
                        <span class="text-muted small fw-semibold text-uppercase"></span>
                    </div>
                    <span class="d-lg-none">${badge}</span>
                </div>
            </td>

            <td class="py-3 text-center card-field-desktop-only" data-label="Estado">${badge}</td>

            <td class="py-3 text-end pe-5 mobile-card-actions" data-label="Acción">${boton}</td>
        </tr>`;
    }).join('');
}

function cambiarEstadoTicket(id, nuevoEstado) {

    const accion = nuevoEstado === 0 ? 'Liberar' : 'Reservar';

    confirmarAccion({
        titulo: `${accion} número`,
        mensaje: `¿Deseas ${accion.toLowerCase()} este número?`,
        onConfirm: async () => {

            try {
                const fd = new FormData();
                fd.append('action', 'cambiar_estado');
                fd.append('id_ticket', id);
                fd.append('status', nuevoEstado);

                const j = await API.post('numeros', fd);

                if (j.success) {
                    alertify.success('Estado Cambiado Correctamente');

                    // 🔥 CLAVE: volver a pintar
                    cargarInventario();

                } else {
                    alertify.error(j.message || 'Error al cambiar estado');
                }

            } catch (e) {
                console.error(e);
                alertify.error('Error de conexión');
            }
        }
    });
}




function limpiarFiltrosNumeros() {
    document.getElementById('searchNumeros').value = '';
    document.getElementById('filterEstado').value = '';
    paginaActual = 1;
    cargarInventario();
}

function debounce(f, t) { let e; return () => { clearTimeout(e); e = setTimeout(f, t); } }

    /* ======================================================
   MODAL IMAGEN - NÚMEROS RESERVADOS
====================================================== */

function abrirModalImagenReservados() {
    const modal = new bootstrap.Modal(document.getElementById('modalImagenReservados'));
    const select = document.getElementById('imagenRifaSelectNums');
    const rifaActual = document.getElementById('filterRifa')?.value || '';

    select.innerHTML = '<option value="">Selecciona una rifa</option>';
    document.querySelectorAll('#filterRifa option').forEach(o => {
        if (o.value) {
            select.innerHTML += `<option value="${o.value}">${o.text}</option>`;
        }
    });

    if (rifaActual) {
        select.value = rifaActual;
    }

    document.getElementById('previewImagenReservados').innerHTML = `
        <div class="d-flex justify-content-center align-items-center text-muted py-5">
            Selecciona una rifa
        </div>`;
    document.getElementById('canvasImagenReservados').innerHTML = '';

    modal.show();

    if (rifaActual) {
        generarPreviewImagenReservados();
    }
}

async function cargarDisponiblesRifa(idRifa) {
    const fd = new FormData();
    fd.append('action', 'obtener_inventario');
    fd.append('id_raffle', idRifa);
    fd.append('status', '0');

    const res = await API.post('numeros', fd);
    return res.success && Array.isArray(res.data) ? res.data : [];
}

function pintarPreviewDisponibles(nombreRifa, data) {
    const preview = document.getElementById('previewImagenReservados');
    const canvas = document.getElementById('canvasImagenReservados');
    const primary = getThemeColor('--raffle-color-primary', '#198754');
    const primarySoft = getThemeColor('--raffle-color-primary-soft', '#f0fdf4');
    const numRadius = getThemeColor('--raffle-number-radius', '12px');

    if (!data.length) {
        preview.innerHTML = '<div class="text-muted text-center py-5">No hay números disponibles</div>';
        canvas.innerHTML = '';
        return;
    }

    preview.innerHTML = `
        <div class="container-fluid px-2">
            <div class="text-center mb-3">
                <h6 class="fw-bold text-success mb-0">${nombreRifa}</h6>
                <small class="text-muted">Números disponibles (${data.length})</small>
            </div>
            <div class="row g-2">
                ${data.map(t => `
                    <div class="col-4 col-sm-3 col-md-2">
                        <div class="admin-num-preview-cell text-center py-2 shadow-sm">
                            <div class="fw-bold fs-6">${t.number_ticket}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>`;

    canvas.innerHTML = `
        <div style="text-align:center;margin-bottom:20px;">
            <h2 style="margin:0;color:${primary};">${nombreRifa}</h2>
            <small style="color:#6c757d;">Números disponibles</small>
        </div>
        <div style="display:grid;grid-template-columns:repeat(10,1fr);gap:12px;">
            ${data.map(t => `
                <div style="border:2px solid ${primary};border-radius:${numRadius};padding:10px;text-align:center;background:${primarySoft};color:${primary};">
                    <div style="font-size:20px;font-weight:700;">${t.number_ticket}</div>
                </div>
            `).join('')}
        </div>`;
}

async function generarPreviewImagenReservados() {
    const select = document.getElementById('imagenRifaSelectNums');
    const idRifa = select?.value;
    const preview = document.getElementById('previewImagenReservados');

    if (!idRifa) {
        preview.innerHTML = `
            <div class="d-flex justify-content-center align-items-center text-muted py-5">
                Selecciona una rifa
            </div>`;
        document.getElementById('canvasImagenReservados').innerHTML = '';
        return;
    }

    const nombreRifa = select.selectedOptions[0]?.textContent || '';

    preview.innerHTML = '<div class="text-center py-5 text-muted">Cargando números...</div>';

    try {
        const data = await cargarDisponiblesRifa(idRifa);
        pintarPreviewDisponibles(nombreRifa, data);
    } catch (e) {
        console.error(e);
        preview.innerHTML = '<div class="text-danger text-center py-5">Error al cargar los números</div>';
        document.getElementById('canvasImagenReservados').innerHTML = '';
    }
}

/* ======================================================
   COMPARTIR (iPhone OK)
====================================================== */

function compartirImagenReservados() {
    const cont = document.getElementById('canvasImagenReservados');
    if (!cont.innerHTML.trim()) {
        alertify.error('Selecciona una rifa con números disponibles');
        return;
    }

    html2canvas(cont, {
        scale: 2,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        canvas.toBlob(blob => {
            const file = new File([blob], 'numeros-disponibles.png', { type: 'image/png' });

            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                navigator.share({
                    files: [file],
                    title: 'Números disponibles',
                    text: 'Listado de números disponibles'
                }).catch(() => {});
            } else {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'numeros-disponibles.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        }, 'image/png');
    });
}