/**
 * numeros-vendidos.js - Listado alineado con ventas
 */
let cache = [], paginaActual = 1;
const registrosPorPagina = 50;

document.addEventListener('DOMContentLoaded', () => {
    cargarRifas();
    cargarVendedores();
    cargarNumeros();

    const elSearch = document.getElementById('searchNumeros');
    if (elSearch) {
        elSearch.addEventListener('input', debounce(() => {
            paginaActual = 1;
            cargarNumeros();
        }, 600));
        elSearch.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                paginaActual = 1;
                cargarNumeros();
            }
        });
    }

    const selPeriodo = document.getElementById('filterPeriodo');
    if (selPeriodo) {
        selPeriodo.addEventListener('change', function () {
            if (this.value !== '') {
                document.getElementById('fecha_inicio').value = '';
                document.getElementById('fecha_fin').value = '';
            }
            paginaActual = 1;
            cargarNumeros();
        });
    }

    ['fecha_inicio', 'fecha_fin'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                if (this.value !== '') {
                    document.getElementById('filterPeriodo').value = '';
                }
                if (document.getElementById('fecha_inicio').value && document.getElementById('fecha_fin').value) {
                    paginaActual = 1;
                    cargarNumeros();
                }
            });
        }
    });

    const elRifa = document.getElementById('filterRifa');
    if (elRifa) elRifa.addEventListener('change', () => { paginaActual = 1; cargarNumeros(); });

    const selVendedor = document.getElementById('filterVendedor');
    if (selVendedor) selVendedor.addEventListener('change', () => { paginaActual = 1; cargarNumeros(); });

    const selMetodo = document.getElementById('filterMetodoPago');
    if (selMetodo) selMetodo.addEventListener('change', () => { paginaActual = 1; cargarNumeros(); });

    const imagenRifaSelect = document.getElementById('imagenRifaSelect');
    if (imagenRifaSelect) imagenRifaSelect.addEventListener('change', generarPreviewImagen);
});

async function cargarVendedores() {
    const data = await API.post('ventas', { action: 'listar_vendedores' });
    const sel = document.getElementById('filterVendedor');
    if (!sel || !data.success) return;

    sel.innerHTML = '<option value="">Todos los vendedores</option>';
    data.data.forEach(v => {
        sel.innerHTML += `<option value="${v.id_admin}">${v.email_admin}</option>`;
    });
}

async function cargarRifas() {
    try {
        const j = await API.post('rifas', { action: 'obtener_rifas' });
        const s = document.getElementById('filterRifa');
        if (j.success && s) {
            s.innerHTML = '<option value="">Todas las rifas</option>';
            (Array.isArray(j.data) ? j.data : [j.data]).forEach(r => {
                s.innerHTML += `<option value="${r.id_raffle}">${r.title_raffle}</option>`;
            });
        }
    } catch (e) { console.error(e); }
}

async function cargarNumeros() {
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const fd = new FormData();
        fd.append('action', 'numeros_vendidos');
        fd.append('search', document.getElementById('searchNumeros')?.value.trim() || '');
        fd.append('id_raffle', document.getElementById('filterRifa')?.value || '');
        fd.append('periodo', document.getElementById('filterPeriodo')?.value || '');
        fd.append('fecha_inicio', document.getElementById('fecha_inicio')?.value || '');
        fd.append('fecha_fin', document.getElementById('fecha_fin')?.value || '');
        fd.append('id_admin', document.getElementById('filterVendedor')?.value || '');
        fd.append('payment_method', document.getElementById('filterMetodoPago')?.value || '');

        const data = await API.post('ventas', fd);
        cache = data.success ? (Array.isArray(data.data) ? data.data : []) : [];
        renderTodo();
    } catch (e) {
        console.error(e);
        cache = [];
        renderTodo();
    } finally {
        if (typeof hidePreloader === 'function') hidePreloader();
    }
}

function renderTodo() {
    const totalPaginas = Math.max(1, Math.ceil(cache.length / registrosPorPagina));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;

    if (typeof PaginationHelper !== 'undefined') {
        const segmento = PaginationHelper.getSegment(cache, paginaActual, registrosPorPagina);
        renderTabla(segmento);
        PaginationHelper.render({
            totalItems: cache.length,
            currentPage: paginaActual,
            limit: registrosPorPagina,
            containerId: 'contenedorPaginacion',
            infoId: 'infoPaginacion',
            callbackName: 'cambiarPagina'
        });
    } else {
        renderTabla(cache);
    }
}

function cambiarPagina(p) {
    const totalPaginas = Math.max(1, Math.ceil(cache.length / registrosPorPagina));
    paginaActual = Math.max(1, Math.min(p, totalPaginas));
    renderTodo();
}

function renderTabla(datos) {
    const tbody = document.getElementById('bodyTabla');
    if (!tbody) return;

    if (!datos || datos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    No se encontraron registros
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = datos.map(t => {
        const f = new Date(t.date_created_sale);
        const fecha = f.toLocaleDateString('es-CO');
        const hora = f.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });

        const inicial = t.name_customer?.trim()?.charAt(0)?.toUpperCase() || 'C';
        const telefonoLimpio = (t.phone_customer || '').replace(/\D/g, '');
        const whatsappUrl = telefonoLimpio
            ? `https://api.whatsapp.com/send?phone=57${telefonoLimpio}`
            : '#';

        const badgeClass = t.payment_method_sale === 'Página Web'
            ? 'bg-success-subtle text-success border-success-subtle'
            : 'bg-primary-subtle text-primary border-primary-subtle';

        const totalLinea = Number(t.price_raffle ?? 0);
        const tickets = [t.number_ticket];

        const mobileHead = renderAdminMobileCardHead({
            inicial,
            name: cellTruncate(t.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(t.phone_customer, whatsappUrl),
            extraLine: renderAdminSellerInner(t.email_admin),
            statusHtml: `<span class="badge ${badgeClass} border px-2 py-1 rounded-pill">${t.payment_method_sale || '—'}</span>`,
            metaHtml: renderCardMetaChips(1, totalLinea),
            rifaHtml: cellRifaName(t.title_raffle),
            numbersHtml: renderAdminNumBadges(tickets, 12),
            codeHtml: renderAdminCodeChip(t.code_sale),
        });

        const desktopClient = renderAdminDesktopClient({
            inicial,
            name: cellTruncate(t.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(t.phone_customer, whatsappUrl),
            extraHtml: renderAdminSellerLine(t.email_admin),
        });

        return `
        <tr class="align-middle border-bottom card-row-venta">
            <td class="d-none mobile-hide">${t.id_sale}</td>

            <td class="py-3 ps-3 mobile-card-head" data-label="">
                ${mobileHead}
                ${desktopClient}
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Código">
                <span class="token-chip">${t.code_sale}</span>
            </td>

            <td class="py-3 col-rifa mobile-field-desktop-only" data-label="Rifa">
                ${renderRifaColumnDesktop(1, t.title_raffle, tickets)}
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Total">
                <span class="fw-bold text-dark">$${totalLinea.toLocaleString('es-CO')}</span>
            </td>

            <td class="py-3 mobile-field-desktop-only" data-label="Método">
                <span class="badge ${badgeClass} border px-3 py-2 rounded-pill">${t.payment_method_sale || '—'}</span>
            </td>

            <td class="py-3" data-label="Fecha">
                <div class="d-flex flex-column text-muted">
                    <span class="text-dark fw-medium">${fecha}</span>
                    <span style="font-size:0.85rem;">${hora}</span>
                </div>
            </td>

            <td class="py-3 text-end pe-3 mobile-card-actions" data-label="">
                <div class="d-lg-none">
                    ${renderAdminMobileActions(`
                        <button type="button" class="btn btn-outline-primary flex-fill" onclick="verRecibo(${t.id_sale})" title="Ver detalle">
                            <i class="ti ti-eye"></i> Ver
                        </button>
                    `)}
                </div>
                <div class="d-none d-lg-block">
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <button class="btn btn-outline-primary" title="Ver detalle" onclick="verRecibo(${t.id_sale})">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function verRecibo(id) {
    const fd = new FormData();
    fd.append('action', 'detalle_venta');
    fd.append('id_sale', id);
    API.post('ventas', fd).then(res => {
        if (res.success) {
            document.getElementById('cuerpoRecibo').innerHTML = res.html_recibo;
            new bootstrap.Modal(document.getElementById('modalRecibo')).show();
        } else {
            alertify.error('No se pudo cargar el recibo');
        }
    });
}

async function generarPreviewImagen() {
    const idRifa = document.getElementById('imagenRifaSelect').value;
    if (!idRifa) return;

    try {
        const fd = new FormData();
        fd.append('action', 'numeros_vendidos');
        fd.append('id_raffle', idRifa);

        const json = await API.post('ventas', fd);
        const data = ordenarNumerosVendidos(json.data || []);

        if (!data.length) {
            document.getElementById('previewImagenRifa').innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="ti ti-inbox fs-1"></i>
                    <p class="mt-2">No hay datos</p>
                </div>`;
            return;
        }

        const nombreRifa = data[0]?.title_raffle || '';
        const totalNumeros = data.length;
        const columnasPorFila = Math.min(12, Math.ceil(Math.sqrt(totalNumeros * 1.5)));
        const primary = getThemeColor('--raffle-color-primary', '#198754');
        const chipBg = getThemeColor('--raffle-color-selected-bg', '#198754');
        const chipText = getThemeColor('--raffle-color-selected-text', '#ffffff');
        const chipBorder = getThemeColor('--raffle-color-selected-border', '#157347');
        const numRadius = getThemeColor('--raffle-number-radius', '12px');

        const disenoHTML = `
            <div style="background: #ffffff; padding: 40px 50px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;">
                <h3 style="text-align: center; margin: 0 0 8px 0; font-weight: 700; font-size: 2rem; color: ${primary}; letter-spacing: -0.5px; text-transform: uppercase;">${nombreRifa}</h3>
                <p style="text-align: center; margin: 0 0 35px 0; color: #7f8c8d; font-size: 1.1rem;">Números vendidos</p>
                <div style="display: grid; grid-template-columns: repeat(${columnasPorFila}, 1fr); gap: 14px; justify-items: center;">
                    ${data.map(row => {
                        const nombreTruncado = truncarNombre(row.name_customer, 18);
                        return `
                        <div style="background: ${chipBg}; border: 2px solid ${chipBorder}; padding: 14px 10px; border-radius: ${numRadius}; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.08); width: 90px; height: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div style="font-size: 1.7rem; font-weight: 700; margin-bottom: 5px; color: ${chipText}; line-height: 1;">${row.number_ticket}</div>
                            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.85); line-height: 1.2; text-transform: capitalize; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; max-width: 100%;">${nombreTruncado}</div>
                        </div>`;
                    }).join('')}
                </div>
            </div>`;

        document.getElementById('previewImagenRifa').innerHTML = `
            <div style="transform: scale(0.7); transform-origin: top center;">
                ${disenoHTML}
            </div>`;

        const cont = document.getElementById('canvasImagenRifa');
        cont.innerHTML = `
            <div style="width: 1400px; display: flex; justify-content: center; align-items: center; background: #ffffff;">
                ${disenoHTML}
            </div>`;
    } catch (e) {
        console.error(e);
    }
}

function ordenarNumerosVendidos(rows) {
    if (!Array.isArray(rows)) return [];

    return [...rows].sort((a, b) => {
        const numeroA = String(a?.number_ticket ?? '').trim();
        const numeroB = String(b?.number_ticket ?? '').trim();

        const esNumericoA = /^\d+$/.test(numeroA);
        const esNumericoB = /^\d+$/.test(numeroB);

        if (esNumericoA && esNumericoB) {
            const diff = Number(numeroA) - Number(numeroB);
            if (diff !== 0) return diff;

            // Empate por valor numerico: respeta longitud para 001 < 01 < 1 si aplica.
            if (numeroA.length !== numeroB.length) return numeroB.length - numeroA.length;
            return numeroA.localeCompare(numeroB);
        }

        return numeroA.localeCompare(numeroB, 'es', { numeric: true, sensitivity: 'base' });
    });
}

function truncarNombre(nombre, maxLength = 18) {
    if (!nombre) return 'Sin nombre';
    nombre = nombre.trim();
    if (nombre.length <= maxLength) return nombre;
    const ultimoEspacio = nombre.lastIndexOf(' ', maxLength);
    if (ultimoEspacio > 0) return nombre.substring(0, ultimoEspacio) + '...';
    return nombre.substring(0, maxLength - 3) + '...';
}

function compartirImagenWhatsApp() {
    const cont = document.getElementById('canvasImagenRifa');
    if (!cont.firstElementChild) {
        alert('Primero selecciona una rifa');
        return;
    }

    html2canvas(cont.firstElementChild, {
        scale: 2.5,
        backgroundColor: '#ffffff',
        useCORS: true,
        width: 1400,
        windowWidth: 1400,
        logging: false,
        imageTimeout: 0,
        removeContainer: true
    }).then(canvas => {
        canvas.toBlob(blob => {
            const file = new File([blob], 'numeros-vendidos.png', { type: 'image/png' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                navigator.share({ files: [file], title: 'Números Vendidos' }).catch(() => descargarImagen(blob));
            } else {
                descargarImagen(blob);
            }
        }, 'image/png', 1.0);
    }).catch(() => alert('Error al generar la imagen. Intenta de nuevo.'));
}

function descargarImagen(blob) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'numeros-vendidos.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function abrirModalImagen() {
    const modalEl = document.getElementById('modalImagenRifa');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const select = document.getElementById('imagenRifaSelect');

    select.innerHTML = '<option value="">Selecciona una rifa</option>';
    document.querySelectorAll('#filterRifa option').forEach(o => {
        if (o.value) select.innerHTML += `<option value="${o.value}">${o.text}</option>`;
    });

    document.getElementById('previewImagenRifa').innerHTML = `
        <div class="d-flex justify-content-center align-items-center text-muted py-5">
            <i class="ti ti-photo fs-1 me-2"></i>
            <span>Selecciona una rifa para generar la imagen</span>
        </div>`;

    modal.show();
}

function limpiarFiltros() {
    document.getElementById('searchNumeros').value = '';
    document.getElementById('filterRifa').value = '';
    document.getElementById('filterPeriodo').value = '';
    document.getElementById('fecha_inicio').value = '';
    document.getElementById('fecha_fin').value = '';
    const selVendedor = document.getElementById('filterVendedor');
    if (selVendedor) selVendedor.value = '';
    const selMetodo = document.getElementById('filterMetodoPago');
    if (selMetodo) selMetodo.value = '';
    paginaActual = 1;
    cargarNumeros();
}

function debounce(f, w) {
    let t;
    return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => f(...a), w);
    };
}
