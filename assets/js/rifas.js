/**
 * rifas.js - Gestión Completa Restaurada (Talla Mundial)
 */
let rifasCache = [], idRifaEliminar = null, modalRifa = null, modalConfirm = null;
let paginaActual = 1;
const registrosPorPagina = 10;

function esAdmin() {
    return window.APP_USER?.isAdmin === true;
}

function escHtmlRifa(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function formatFechaRifa(fechaStr) {
    if (!fechaStr || fechaStr === '-') return '-';
    const d = new Date(String(fechaStr).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return fechaStr;
    return d.toLocaleString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function renderPremiosHtml(texto) {
    if (!texto) return '';
    const partes = String(texto).split(',').map(s => s.trim()).filter(Boolean);
    if (partes.length <= 1) {
        return `<p class="rifa-card__text">${escHtmlRifa(texto)}</p>`;
    }
    return `<ul class="rifa-premios-list">${partes.map(p => `<li>${escHtmlRifa(p)}</li>`).join('')}</ul>`;
}

function urlPublicaRifa(idRaffle) {
    const base = (window.SITE_URL || window.location.origin).replace(/\/$/, '');
    return `${base}/?env=${idRaffle}`;
}

document.addEventListener('DOMContentLoaded', function() {
    initRifaModals();

    const esGratisEl = document.getElementById('esGratis');
    if (esGratisEl) {
        esGratisEl.addEventListener('change', syncPrecioGratisUI);
    }

    if (document.getElementById('listaRifas')) {
        cargarRifas();
        document.getElementById('listaRifas').addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy-enlace]');
            if (!btn) return;
            e.preventDefault();
            copiarEnlaceRifa(btn.getAttribute('data-copy-enlace'));
        });
    }

    const inputSearch = document.getElementById('searchRifas');
    if (inputSearch) inputSearch.addEventListener('input', debounce(() => { paginaActual = 1; cargarRifas(); }, 500));

    const selectStatus = document.getElementById('filterStatus');
    if (selectStatus) selectStatus.addEventListener('change', () => { paginaActual = 1; cargarRifas(); });
});

function initRifaModals() {
    const elModalRifa = document.getElementById('modalRifa');
    const elModalConfirm = document.getElementById('modalConfirm');

    if (elModalRifa && typeof bootstrap !== 'undefined') {
        modalRifa = bootstrap.Modal.getOrCreateInstance(elModalRifa);
    }
    if (elModalConfirm && typeof bootstrap !== 'undefined') {
        modalConfirm = bootstrap.Modal.getOrCreateInstance(elModalConfirm);
    }
}

function setVal(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.value = (value !== null && value !== undefined) ? value : '';
    }
}

function setTxt(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

async function cargarRifas() {
    if (typeof showPreloader === 'function') showPreloader();
    const container = document.getElementById('listaRifas');
    if (container) {
        container.innerHTML = '<div class="text-center py-5 text-muted rifas-cards-grid__empty">Cargando rifas...</div>';
    }
    try {
        const searchInput = document.getElementById('searchRifas');
        const statusSelect = document.getElementById('filterStatus');

        const data = await API.post('rifas', {
            action: 'obtener',
            search: searchInput ? searchInput.value.trim() : '',
            status: statusSelect ? statusSelect.value : '',
        });

        if (data.success) {
            rifasCache = data.data || [];
            renderizarTodo();
        } else {
            rifasCache = [];
            renderizarTodo();
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        if (typeof hidePreloader === 'function') hidePreloader();
    }
}

function renderizarTodo() {
    if (typeof PaginationHelper === 'undefined') return;
    const totalPaginas = Math.max(1, Math.ceil(rifasCache.length / registrosPorPagina));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;
    const segmento = PaginationHelper.getSegment(rifasCache, paginaActual, registrosPorPagina);
    renderRifasCards(segmento);
    PaginationHelper.render({
        totalItems: rifasCache.length,
        currentPage: paginaActual,
        limit: registrosPorPagina,
        containerId: 'contenedorPaginacion',
        infoId: 'infoPaginacion',
        callbackName: 'cambiarPagina'
    });
}

function renderRifasCards(rifas) {
    const container = document.getElementById('listaRifas');
    if (!container) return;

    if (!rifas || rifas.length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted rifas-cards-grid__empty">No hay rifas registradas</div>';
        return;
    }

    container.innerHTML = rifas.map(r => {
        const activo = parseInt(r.status_raffle) === 1;
        const enlace = urlPublicaRifa(r.id_raffle);
        const precio = parseFloat(r.price_raffle).toLocaleString('es-CO');
        const fecha = formatFechaRifa(r.date_raffle);
        const badgeClass = activo
            ? 'bg-success-subtle text-success border border-success-subtle'
            : 'bg-danger-subtle text-danger border border-danger-subtle';

        return `
        <article class="rifa-card">
            <header class="rifa-card__head">
                <div class="rifa-card__head-main">
                    <span class="rifa-card__id">#${r.id_raffle}</span>
                    <h3 class="rifa-card__title">${escHtmlRifa(r.title_raffle)}</h3>
                </div>
                <span class="badge ${badgeClass} px-2 py-1 flex-shrink-0">${activo ? 'Activa' : 'Inactiva'}</span>
            </header>

            <div class="rifa-card__meta">
                <span><i class="ti ti-coin"></i> ${parseInt(r.is_free_raffle) === 1 ? 'Gratis' : '$' + precio}</span>
                <span><i class="ti ti-hash"></i> ${r.digits_raffle} cifras</span>
                <span><i class="ti ti-calendar"></i> ${escHtmlRifa(fecha)}</span>
            </div>

            ${r.description_raffle ? `
            <div class="rifa-card__block">
                <span class="rifa-card__label">Lotería</span>
                <p class="rifa-card__text">${escHtmlRifa(r.description_raffle)}</p>
            </div>` : ''}

            ${r.promotions_raffle ? `
            <div class="rifa-card__block">
                <span class="rifa-card__label">Premios</span>
                ${renderPremiosHtml(r.promotions_raffle)}
            </div>` : ''}

            <div class="rifa-card__link">
                <a href="${enlace}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="ti ti-external-link me-1"></i> Abrir rifa
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" data-copy-enlace="${escHtmlRifa(enlace)}" title="Copiar enlace">
                    <i class="ti ti-copy"></i>
                </button>
            </div>

            <div class="rifa-card__link">
                <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="descargarInformeVentas(${r.id_raffle})" title="Descargar informe de ventas">
                    <i class="ti ti-file-spreadsheet me-1"></i> Informe Excel
                </button>
            </div>

            <footer class="rifa-card__actions">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarRifa(${r.id_raffle})">
                    <i class="ti ti-edit me-1"></i> Editar
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="reutilizarRifa(${r.id_raffle})" title="Reutilizar">
                    <i class="ti ti-recycle me-1"></i> Reutilizar
                </button>
                ${esAdmin() ? `
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarRifa(${r.id_raffle})" title="Eliminar">
                    <i class="ti ti-trash me-1"></i> Eliminar
                </button>` : ''}
            </footer>
        </article>`;
    }).join('');
}

function copiarEnlaceRifa(url) {
    if (!url) {
        alertify.error('Enlace no válido');
        return;
    }

    const copiado = () => alertify.success('Enlace copiado');

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(url)
            .then(copiado)
            .catch(() => {
                if (copiarEnlaceRifaFallback(url)) copiado();
                else alertify.error('No se pudo copiar');
            });
        return;
    }

    if (copiarEnlaceRifaFallback(url)) copiado();
    else alertify.error('No se pudo copiar');
}

function copiarEnlaceRifaFallback(text) {
    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '0';
        ta.style.left = '0';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        ta.setSelectionRange(0, text.length);
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
    } catch (e) {
        return false;
    }
}

window.copiarEnlaceRifa = copiarEnlaceRifa;

function descargarInformeVentas(idRaffle) {
    if (!idRaffle) {
        alertify.error('Rifa no válida');
        return;
    }
    const base = (window.SITE_URL || window.location.origin).replace(/\/$/, '');
    window.location.href = `${base}/front/export-ventas-rifa.php?id_raffle=${encodeURIComponent(idRaffle)}`;
}

window.descargarInformeVentas = descargarInformeVentas;

function syncPrecioGratisUI() {
    const esGratis = document.getElementById('esGratis');
    const precioEl = document.getElementById('precio');
    if (!esGratis || !precioEl) return;

    const gratis = esGratis.checked;
    precioEl.disabled = gratis;
    precioEl.required = !gratis;
    if (gratis) {
        precioEl.value = '0';
    }
}

function abrirModal() {
    const form = document.getElementById('formRifa');
    if (form) form.reset();
    setVal('rifaId', '');
    setVal('promociones', '');
    const esGratis = document.getElementById('esGratis');
    if (esGratis) esGratis.checked = false;
    syncPrecioGratisUI();
    setTxt('modalTitle', 'Nueva Rifa');
    if(modalRifa) modalRifa.show();
}

function editarRifa(id) {
    const r = rifasCache.find(x => parseInt(x.id_raffle) === parseInt(id));
    if (!r) return;

    setVal('rifaId', r.id_raffle);
    setVal('titulo', r.title_raffle);
    setVal('descripcion', r.description_raffle);
    setVal('promociones', r.promotions_raffle);
    setVal('precio', r.price_raffle);
    setVal('cifras', r.digits_raffle);
    setVal('fecha', r.date_raffle ? r.date_raffle.replace(" ", "T") : '');
    setVal('estado', r.status_raffle);
    const esGratis = document.getElementById('esGratis');
    if (esGratis) esGratis.checked = parseInt(r.is_free_raffle) === 1;
    syncPrecioGratisUI();

    setTxt('modalTitle', 'Editar Rifa');
    if(modalRifa) modalRifa.show();
}

async function guardarRifa() {
    const id = document.getElementById('rifaId')?.value;
    const formData = new FormData();
    formData.append('action', id ? 'actualizar' : 'crear');
    formData.append('id_raffle', id || '');
    formData.append('title_raffle', document.getElementById('titulo')?.value.trim() || '');
    formData.append('description_raffle', document.getElementById('descripcion')?.value.trim() || '');
    formData.append('promotions_raffle', document.getElementById('promociones')?.value.trim() || '');
    formData.append('price_raffle', document.getElementById('precio')?.value || '0');
    formData.append('digits_raffle', document.getElementById('cifras')?.value || '4');
    formData.append('date_raffle', document.getElementById('fecha')?.value || '');
    const esGratisEl = document.getElementById('esGratis');
    formData.append('is_free_raffle', esGratisEl && esGratisEl.checked ? '1' : '0');
    const estadoEl = document.getElementById('estado');
    formData.append('status_raffle', estadoEl ? estadoEl.value : '1');

    if (typeof showPreloader === 'function') showPreloader();
    try {
        const data = await API.post('rifas', formData);
        if (data.success) {
            alertify.success(data.message);
            if(modalRifa) modalRifa.hide();
            cargarRifas();
        } else {
            alertify.error(data.message);
        }
    } catch (e) { alertify.error("Error en la solicitud"); }
    finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function eliminarRifa(id) {
    idRifaEliminar = id;
    if(modalConfirm) modalConfirm.show();
}

async function confirmarEliminar() {
    const fd = new FormData();
    fd.append('action', 'eliminar');
    fd.append('id_raffle', idRifaEliminar);
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const data = await API.post('rifas', fd);
        if (data.success) {
            alertify.success('Eliminado');
            if (modalConfirm) modalConfirm.hide();
            cargarRifas();
        } else {
            alertify.error(data.message || 'No se pudo eliminar la rifa');
        }
    } catch (e) {
        console.error(e);
        alertify.error('Error de conexión');
    } finally {
        if (typeof hidePreloader === 'function') hidePreloader();
    }
}

function reutilizarRifa(id) {
    confirmarAccion({
        titulo: '¿Reutilizar rifa?',
        html: `<p class="text-muted mb-0">Se cancelarán las <strong>reservas activas</strong>, se eliminarán las <strong>ventas</strong> y todos los números quedarán <strong>disponibles</strong> de nuevo.</p>`,
        textoConfirmar: 'Sí, reutilizar',
        tipoConfirmar: 'success',
        onConfirm: async () => {
            const fd = new FormData();
            fd.append('action', 'reutilizar');
            fd.append('id_raffle', id);

            const data = await API.post('rifas', fd);

            if (!data.success) {
                alertify.error(data.message || 'No se pudo reutilizar la rifa');
                throw new Error(data.message || 'reuse_failed');
            }

            await cargarRifas();

            return {
                inform: {
                    titulo: 'Rifa reutilizada',
                    mensaje: data.message || 'Se cancelaron las reservas activas, se eliminaron las ventas y todos los números quedaron disponibles.',
                },
            };
        },
    });
}

window.reutilizarRifa = reutilizarRifa;
window.eliminarRifa = eliminarRifa;
window.confirmarEliminar = confirmarEliminar;

function cambiarPagina(p) {
    const totalPaginas = Math.max(1, Math.ceil(rifasCache.length / registrosPorPagina));
    paginaActual = Math.max(1, Math.min(p, totalPaginas));
    renderizarTodo();
}
function debounce(f, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), w); }; }

function limpiarFiltros() {
    setVal('searchRifas', '');
    setVal('filterStatus', '');
    paginaActual = 1;
    cargarRifas();
}
