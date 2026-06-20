/**
 * clientes.js - Gestión Total Blindada (Sin Omisiones)
 */
let clientesCache = [], idClienteEliminar = null, modalCliente = null, modalConfirm = null;
let paginaActual = 1;
const registrosPorPagina = 10;

// ==========================================
// FUNCIONES GLOBALES (ACCESIBLES DESDE EL HTML)
// ==========================================

/**
 * Abre el modal para crear un nuevo cliente
 */
function abrirModal() {
    const form = document.getElementById('formCliente');
    if (form) form.reset();

    setVal('clienteId', '');
    document.getElementById('modalTitle').textContent = 'Nuevo Cliente';
   


    if (modalCliente) modalCliente.show();
}

/**
 * Cambia la página actual del listado
 */
function cambiarPagina(p) {
    const totalPaginas = Math.max(1, Math.ceil(clientesCache.length / registrosPorPagina));
    paginaActual = Math.max(1, Math.min(p, totalPaginas));
    renderizarTodo();
}

/**
 * Helper para asignar valores a inputs, permitiendo el valor 0 (Inactivo)
 */
function setVal(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.value = (value !== null && value !== undefined) ? value : '';
    }
}

// ==========================================
// INICIALIZACIÓN Y CARGA
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const elModalCliente = document.getElementById('modalCliente');
    const elModalConfirm = document.getElementById('modalConfirm');

    if (elModalCliente) modalCliente = bootstrap.Modal.getOrCreateInstance(elModalCliente);
    if (elModalConfirm) modalConfirm = bootstrap.Modal.getOrCreateInstance(elModalConfirm);


    // Activar lógica de departamentos-ciudades.js si existe
    if (typeof inicializarUbicacion === 'function') inicializarUbicacion();

    if (document.getElementById('bodyTabla')) cargarClientes();

    const inputSearch = document.getElementById('searchClientes');
    if (inputSearch) inputSearch.addEventListener('input', debounce(() => { paginaActual = 1; cargarClientes(); }, 500));

    const selectStatus = document.getElementById('filterStatus');
    if (selectStatus) selectStatus.addEventListener('change', () => { paginaActual = 1; cargarClientes(); });
});

async function cargarClientes() {
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const formData = new FormData();
        formData.append('action', 'obtener');
        formData.append('search', document.getElementById('searchClientes')?.value.trim() || '');
        formData.append('status', document.getElementById('filterStatus')?.value || '');

        const data = await API.post('clientes', formData);

        if (data.success) {
            clientesCache = data.data || [];
            renderizarTodo();
        } else {
            clientesCache = [];
            renderizarTodo();
        }
    } catch (e) { console.error("Error al cargar:", e); }
    finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function renderizarTodo() {
    if (typeof PaginationHelper === 'undefined') return;
    const totalPaginas = Math.max(1, Math.ceil(clientesCache.length / registrosPorPagina));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;
    const segmento = PaginationHelper.getSegment(clientesCache, paginaActual, registrosPorPagina);
    renderTabla(segmento);
    PaginationHelper.render({
        totalItems: clientesCache.length, currentPage: paginaActual, limit: registrosPorPagina,
        containerId: 'contenedorPaginacion', infoId: 'infoPaginacion', callbackName: 'cambiarPagina'
    });
}

    function renderTabla(clientes) {
    const tbody = document.getElementById('bodyTabla');
    if (!tbody) return;

    if (!clientes || clientes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center py-5 text-muted">
                    No se encontraron clientes
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = clientes.map(c => {

        // Inicial avatar
        const inicial = c.name_customer?.trim()?.charAt(0)?.toUpperCase() || 'C';

        // Estado
        const activo = parseInt(c.status_customer) === 1;

        // WhatsApp
        const telefonoLimpio = (c.phone_customer || '').replace(/\D/g, '');
        const whatsappUrl = telefonoLimpio
            ? `https://api.whatsapp.com/send?phone=57${telefonoLimpio}`
            : '#';

        const mobileHead = renderAdminMobileCardHead({
            inicial,
            name: cellTruncate(c.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(c.phone_customer, whatsappUrl),
        });

        const desktopClient = renderAdminDesktopClient({
            inicial,
            name: cellTruncate(c.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(c.phone_customer, whatsappUrl),
        });

        return `
        <tr class="align-middle border-bottom hover-shadow">

            <td class="py-3 ps-3 mobile-card-head" data-label="">
                ${mobileHead}
                ${desktopClient}
            </td>

            <!-- ESTADO -->
            <td class="py-3 text-center" data-label="Estado">
                <span class="badge ${
                    activo
                        ? 'badge bg-success-subtle text-success border border-success-subtle px-3'
                        : 'badge bg-danger-subtle text-danger border border-danger-subtle px-3'
                } px-3 py-2 rounded-pill">
                    ${activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>

            <!-- ACCIONES -->
            <td class="py-3 text-end pe-3 mobile-card-actions" data-label="">
                <button class="btn btn-icon btn-sm btn-outline-secondary border-0 rounded-circle shadow-sm"
                        onclick="editarCliente(${c.id_customer})"
                        title="Editar Cliente"
                        style="width: 32px; height: 32px;">
                    <i class="ti ti-edit fs-7"></i>
                </button>
            </td>

        </tr>`;
    }).join('');
}





function editarCliente(id) {
    const c = clientesCache.find(x => parseInt(x.id_customer) === parseInt(id));
    if (!c) return;

    setVal('clienteId', c.id_customer);
    setVal('nombre', c.name_customer);
    setVal('telefono', c.phone_customer);
    setVal('estado', c.status_customer);

    document.getElementById('modalTitle').textContent = 'Editar Cliente';
    if (modalCliente) modalCliente.show();
}

async function guardarCliente() {
    const id = document.getElementById('clienteId').value;
    const formData = new FormData();

    formData.append('action', id ? 'actualizar' : 'crear');
    formData.append('id_customer', id);
    formData.append('name_customer', document.getElementById('nombre').value.trim());
    formData.append('phone_customer', document.getElementById('telefono').value.trim());
    formData.append('status_customer', document.getElementById('estado').value);


    if (typeof showPreloader === 'function') showPreloader();
    try {
        const data = await API.post('clientes', formData);
        if (data.success) {
            alertify.success(data.message);
            if (modalCliente) modalCliente.hide();
            cargarClientes();
        } else { alertify.error(data.message); }
    } catch (e) { alertify.error("Error en la solicitud"); }
    finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function eliminarCliente(id) {
    idClienteEliminar = id;
    if (modalConfirm) modalConfirm.show();
}

async function confirmarEliminar() {
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const fd = new FormData();
        fd.append('action', 'eliminar');
        fd.append('id_customer', idClienteEliminar);
        const data = await API.post('clientes', fd);
        if (data.success) {
            alertify.success('Eliminado');
            if (modalConfirm) modalConfirm.hide();
            cargarClientes();
        }
    } finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function limpiarFiltros() {
    document.getElementById('searchClientes').value = '';
    document.getElementById('filterStatus').value = '';
    paginaActual = 1;
    cargarClientes();
}

function debounce(f, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), w); }; }