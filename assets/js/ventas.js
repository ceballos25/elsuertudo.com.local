/**
 * ventas.js - Gestión Blindada y Completa
 */
let ventasPagina = [],
    paginacionVentas = { page: 1, per_page: 10, total: 0, total_pages: 1 },
    paginaActual = 1,
    ventaGestion = null,
    clienteOriginal = null,
    clienteGestionBusqueda = { encontrado: false, id: null, autoNombre: false },
    modalGestionInstance = null;

const registrosPorPagina = 10;

function esAdmin() {
    return window.APP_USER?.isAdmin === true;
}


document.addEventListener('DOMContentLoaded', function () {

    // 1️⃣ Cargar Rifas
    cargarRifasSelect();

    // 2️⃣ Cargar Ventas Iniciales
    cargarVentas();

    cargarVendedores();

    const selVendedor = document.getElementById('filterVendedor');
    if (selVendedor) {
        selVendedor.addEventListener('change', () => {
            paginaActual = 1;
            cargarVentas();
        });
    }



    const selMetodoPago = document.getElementById('filterMetodoPago');
    if (selMetodoPago) {
        selMetodoPago.addEventListener('change', () => {
            paginaActual = 1;
            cargarVentas();
        });
    }
    
    const inputSearch = document.getElementById('searchVentas');
    if (inputSearch) {
        inputSearch.addEventListener('input', debounce(() => {
            paginaActual = 1;
            cargarVentas();
        }, 600));
        inputSearch.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                paginaActual = 1;
                cargarVentas();
            }
        });
    }

    // 4. Listener: Select Periodo (Limpia fechas manuales)
    const selPeriodo = document.getElementById('filterPeriodo');
    if (selPeriodo) {
        selPeriodo.addEventListener('change', function() {
            if (this.value !== "") {
                document.getElementById('fecha_inicio').value = '';
                document.getElementById('fecha_fin').value = '';
            }
            paginaActual = 1;
            cargarVentas();
        });
    }

    // 5. Listener: Fechas Manuales (Limpia periodo)
    ['fecha_inicio', 'fecha_fin'].forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            el.addEventListener('change', function() {
                if (this.value !== "") {
                    document.getElementById('filterPeriodo').value = '';
                }
                // Si ambas fechas están llenas, recarga
                if(document.getElementById('fecha_inicio').value && document.getElementById('fecha_fin').value){
                    paginaActual = 1;
                    cargarVentas();
                }
            });
        }
    });

    // 6. Listener: Select Rifa
    const selRifa = document.getElementById('filterRifa');
    if (selRifa) {
        selRifa.addEventListener('change', function() {
            paginaActual = 1;
            cargarVentas();
        });
    }

    const gestionNombre = document.getElementById('gestionNombre');
    const gestionTelefono = document.getElementById('gestionTelefono');
    if (gestionNombre) gestionNombre.addEventListener('input', actualizarEstadoFormularioCliente);
    if (gestionTelefono) {
        gestionTelefono.addEventListener('input', onGestionTelefonoInput);
        gestionTelefono.addEventListener('paste', () => setTimeout(onGestionTelefonoInput, 0));
    }

    document.addEventListener('change', e => {
        if (e.target.classList.contains('ticket-liberar')) {
            actualizarBotonLiberar();
        }
    });
});

async function cargarVendedores() {
    const data = await API.post('ventas', { action: 'listar_vendedores' });
    const sel = document.getElementById('filterVendedor');
    if (!sel) return;

    if (data.success) {
        sel.innerHTML = '<option value="">Todos</option>';
        data.data.forEach(v => {
            sel.innerHTML += `<option value="${v.id_admin}">${v.email_admin}</option>`;
        });
    }
}

// Obtener Rifas para el Select
async function cargarRifasSelect() {
    try {
        const data = await API.post('rifas', { action: 'obtener_rifas' });
        if (data.success && data.data) {
            const select = document.getElementById('filterRifa');
            select.innerHTML = '<option value="">Todas las rifas</option>';
            const lista = Array.isArray(data.data) ? data.data : [data.data];
            lista.forEach(r => {
                select.innerHTML += `<option value="${r.id_raffle}">${r.title_raffle}</option>`;
            });
        }
    } catch (e) { console.error("Error rifas", e); }
}

// Cargar Ventas (Main)
async function cargarVentas() {
    if (typeof showPreloader === 'function') showPreloader();
    try {
        const fd = new FormData();
        fd.append('action', 'obtener');
        fd.append('search', document.getElementById('searchVentas')?.value.trim() || '');
        fd.append('periodo', document.getElementById('filterPeriodo')?.value || '');
        fd.append('id_raffle', document.getElementById('filterRifa')?.value || '');
        fd.append('fecha_inicio', document.getElementById('fecha_inicio')?.value || '');
        fd.append('fecha_fin', document.getElementById('fecha_fin')?.value || '');
        fd.append('id_admin', document.getElementById('filterVendedor')?.value || '');
        fd.append('payment_method', document.getElementById('filterMetodoPago')?.value || '');
        fd.append('page', paginaActual);
        fd.append('per_page', registrosPorPagina);

        const data = await API.post('ventas', fd);

        if (data.success) {
            ventasPagina = Array.isArray(data.data) ? data.data : (data.data ? [data.data] : []);
            if (data.pagination) {
                paginacionVentas = data.pagination;
                paginaActual = data.pagination.page;
            }
        } else {
            ventasPagina = [];
            paginacionVentas = { page: 1, per_page: registrosPorPagina, total: 0, total_pages: 1 };
        }
        renderPaginaVentas();
    } catch (e) { console.error(e); }
    finally { if (typeof hidePreloader === 'function') hidePreloader(); }
}

function renderPaginaVentas() {
    renderTabla(ventasPagina);

    if (typeof PaginationHelper !== 'undefined') {
        PaginationHelper.render({
            totalItems: paginacionVentas.total,
            currentPage: paginaActual,
            limit: registrosPorPagina,
            containerId: 'contenedorPaginacion',
            infoId: 'infoPaginacion',
            callbackName: 'cambiarPagina'
        });
    }
}

function cambiarPagina(p) {
    const totalPaginas = paginacionVentas.total_pages || 1;
    paginaActual = Math.max(1, Math.min(p, totalPaginas));
    cargarVentas();
}

function renderTabla(ventas) {
    const tbody = document.getElementById('bodyTabla');
    if (!tbody) return;

    if (!ventas || ventas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    No se encontraron registros
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = ventas.map(v => {

        // const f = new Date(v.date_created_sale);
        // const fecha = f.toLocaleDateString('es-CO');
        // const hora = f.toLocaleTimeString('es-CO', {
        //     hour: '2-digit',
        //     minute: '2-digit',
        //     hour12: true
        // });
        
        const f = new Date(
            new Date(v.date_created_sale).getTime() - (5 * 60 * 60 * 1000)
        );
        
        const fecha = f.toLocaleDateString('es-CO');
        const hora = f.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });


        // Avatar
        const inicial = v.name_customer?.trim()?.charAt(0)?.toUpperCase() || 'C';

        // WhatsApp
        const telefonoLimpio = (v.phone_customer || '').replace(/\D/g, '');
        const whatsappUrl = telefonoLimpio
            ? `https://api.whatsapp.com/send?phone=57${telefonoLimpio}`
            : '#';

        const badgeClass = v.payment_method_sale === 'Página Web'
            ? 'bg-success-subtle text-success border-success-subtle'
            : 'bg-primary-subtle text-primary border-primary-subtle';

        const puedeCancelar = Number(v.status_sale) === 1;

        const btnCancelar = puedeCancelar && esAdmin()
            ? `<button class="btn btn-outline-secondary" onclick="gestionarVenta(${v.id_sale})" title="Gestionar venta"><i class="ti ti-settings"></i></button>`
            : '';
        const btnGestionMobile = puedeCancelar && esAdmin()
            ? `<button class="btn btn-outline-secondary flex-fill" onclick="gestionarVenta(${v.id_sale})" title="Gestionar venta"><i class="ti ti-settings"></i> Gestionar</button>`
            : '';
        const filaClase = puedeCancelar ? 'card-row-venta' : 'opacity-50';
        const tickets = Array.isArray(v.tickets) ? v.tickets : [];
        const numerosHTML = renderAdminNumBadges(tickets, 12);

        const mobileHead = renderAdminMobileCardHead({
            inicial,
            name: cellTruncate(v.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(v.phone_customer, whatsappUrl),
            extraLine: renderAdminSellerInner(v.email_admin),
            statusHtml: `<span class="badge ${badgeClass} border px-2 py-1 rounded-pill">${v.payment_method_sale}</span>`,
            metaHtml: renderCardMetaChips(v.quantity_sale, v.total_sale),
            rifaHtml: cellRifaName(v.title_raffle),
            numbersHtml: numerosHTML,
            codeHtml: renderAdminCodeChip(v.code_sale),
        });

        const desktopClient = renderAdminDesktopClient({
            inicial,
            name: cellTruncate(v.name_customer),
            phoneHtml: renderAdminWhatsAppPhone(v.phone_customer, whatsappUrl),
            extraHtml: renderAdminSellerLine(v.email_admin),
        });

return `
<tr class="align-middle border-bottom ${filaClase}">
    <td class="d-none mobile-hide">${v.id_sale}</td>

    <td class="py-3 ps-3 mobile-card-head" data-label="">
        ${mobileHead}
        ${desktopClient}
    </td>

    <td class="py-3 mobile-field-desktop-only" data-label="Código">
        <span class="token-chip">${v.code_sale}</span>
    </td>

    <td class="py-3 col-rifa mobile-field-desktop-only" data-label="Rifa">
        ${renderRifaColumnDesktop(v.quantity_sale, v.title_raffle, tickets)}
    </td>

    <td class="py-3 mobile-field-desktop-only" data-label="Total">
        <span class="fw-bold text-dark">$${Number(v.total_sale).toLocaleString('es-CO')}</span>
    </td>

    <td class="py-3 mobile-field-desktop-only" data-label="Método">
        <span class="badge ${badgeClass} border px-3 py-2 rounded-pill">${v.payment_method_sale}</span>
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
                <button class="btn btn-outline-primary flex-fill" onclick="verRecibo(${v.id_sale})" title="Ver Detalle">
                    <i class="ti ti-eye"></i> Ver
                </button>
                ${btnGestionMobile}
            `)}
        </div>
        <div class="d-none d-lg-block">
            <div class="btn-group btn-group-sm shadow-sm" role="group">
                <button class="btn btn-outline-primary" onclick="verRecibo(${v.id_sale})" title="Ver Detalle">
                    <i class="ti ti-eye"></i>
                </button>
                ${btnCancelar}
            </div>
        </div>
    </td>
</tr>`;
   }).join('');
}




function limpiarFiltrosVentas() {
    document.getElementById('searchVentas').value = '';
    document.getElementById('filterPeriodo').value = '';
    document.getElementById('filterRifa').value = '';
    document.getElementById('fecha_inicio').value = '';
    document.getElementById('fecha_fin').value = '';
    const selVendedor = document.getElementById('filterVendedor');
    if (selVendedor) selVendedor.value = '';
    const selMetodo = document.getElementById('filterMetodoPago');
    if (selMetodo) selMetodo.value = '';
    paginaActual = 1;
    cargarVentas();
}

function debounce(f, w) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), w); }; }

function verRecibo(id) {
    const fd = new FormData();
    fd.append('action', 'detalle_venta');
    fd.append('id_sale', id);
    API.post('ventas', fd).then(res => {
            if (res.success) {
                document.getElementById('cuerpoRecibo').innerHTML = res.html_recibo;
                new bootstrap.Modal(document.getElementById('modalRecibo')).show();
            } else {                
                alertify.error("No se pudo cargar el recibo")
            }
        });
}

async function gestionarVenta(idSale, opts = {}) {
    if (typeof showPreloader === 'function') showPreloader();
    if (!opts.keepFeedback) ocultarGestionFeedback();

    try {
        const res = await API.post('ventas', { action: 'gestion_venta', id_sale: idSale });

        if (!res.success) {
            alertify.error(res.message || 'No se pudo cargar la venta');
            return;
        }

        ventaGestion = res.data;
        clienteOriginal = {
            nombre: ventaGestion.name_customer || '',
            celular: (ventaGestion.phone_customer || '').replace(/\D/g, ''),
        };

        renderGestionVenta();

        const modalEl = document.getElementById('modalGestionVenta');
        modalGestionInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalGestionInstance.show();
    } catch (e) {
        console.error(e);
        alertify.error('No se pudo conectar con el servidor. Intenta de nuevo.');
    } finally {
        if (typeof hidePreloader === 'function') hidePreloader();
    }
}

function ocultarGestionFeedback() {
    const box = document.getElementById('gestionFeedback');
    if (!box) return;
    box.className = 'alert d-none mb-3';
    box.innerHTML = '';
}

function mostrarGestionFeedback(tipo, titulo, detalle) {
    const box = document.getElementById('gestionFeedback');
    if (!box) return;

    const clases = {
        success: 'alert-success',
        error: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info',
    };

    box.className = `alert ${clases[tipo] || 'alert-info'} mb-3`;
    box.innerHTML = `
        <div class="fw-bold">${titulo}</div>
        ${detalle ? `<div class="small mt-1 mb-0">${detalle}</div>` : ''}
    `;
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderGestionVenta() {
    if (!ventaGestion) return;

    document.querySelector('#gestionResumen .card-body').innerHTML = `
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <span><strong>Código:</strong> <span class="font-monospace">${ventaGestion.code_sale}</span></span>
            <span><strong>Rifa:</strong> ${ventaGestion.title_raffle}</span>
            <span><strong>Números:</strong> ${ventaGestion.quantity_sale}</span>
            <span><strong>Total:</strong> $${Number(ventaGestion.total_sale).toLocaleString('es-CO')}</span>
        </div>
    `;

    document.getElementById('gestionClienteActual').innerHTML = `
        <div class="fw-semibold text-dark">${ventaGestion.name_customer}</div>
        <div class="text-muted"><i class="ti ti-phone"></i> ${ventaGestion.phone_customer || 'Sin celular'}</div>
    `;

    document.getElementById('gestionNombre').value = '';
    document.getElementById('gestionTelefono').value = '';
    resetClienteGestionBusqueda();

    const contenedor = document.getElementById('gestionNumeros');
    const btnLiberar = document.getElementById('btnLiberarNumeros');

    if (!ventaGestion.tickets?.length) {
        contenedor.innerHTML = '<span class="text-muted small">No hay números activos en esta venta.</span>';
        if (btnLiberar) btnLiberar.disabled = true;
    } else {
        contenedor.innerHTML = ventaGestion.tickets.map(t => `
            <label class="ticket-release-item">
                <span class="ticket-release-check">
                    <input type="checkbox" class="form-check-input ticket-liberar" value="${t.id_ticket}" data-numero="${t.number_ticket}" aria-label="Marcar ${t.number_ticket}">
                </span>
                <span class="ticket-release-num">${t.number_ticket}</span>
            </label>
        `).join('');
        if (btnLiberar) btnLiberar.disabled = true;
    }

    actualizarEstadoFormularioCliente();
    aplicarPermisosGestionVenta();
}

function aplicarPermisosGestionVenta() {
    const admin = esAdmin();
    const modal = document.getElementById('modalGestionVenta');
    if (!modal) return;

    modal.querySelectorAll('[data-admin-only]').forEach(el => {
        el.classList.toggle('d-none', !admin);
    });

    const subtitle = modal.querySelector('.modal-header small');
    if (subtitle) {
        subtitle.textContent = admin
            ? 'Modifica el cliente o libera números sin anular toda la venta'
            : 'Consulta los datos de la venta';
    }
}

function normalizarCelularGestion(valor) {
    let val = (valor || '').replace(/\D/g, '');
    if (val.startsWith('57') && val.length > 10) val = val.substring(2);
    return val;
}

function resetClienteGestionBusqueda() {
    clienteGestionBusqueda = { encontrado: false, id: null, autoNombre: false };
    const nombre = document.getElementById('gestionNombre');
    if (nombre) {
        nombre.readOnly = false;
        nombre.classList.remove('bg-light');
    }
    mostrarEstadoClienteGestion(null);
}

function mostrarEstadoClienteGestion(tipo, mensaje) {
    const el = document.getElementById('gestionClienteEstado');
    if (!el) return;

    if (!tipo) {
        el.className = 'small mt-2 d-none';
        el.innerHTML = '';
        return;
    }

    const clases = {
        success: 'text-success',
        info: 'text-primary',
        warning: 'text-warning',
        muted: 'text-muted',
    };

    el.className = `small mt-2 ${clases[tipo] || 'text-muted'}`;
    el.innerHTML = mensaje;
    el.classList.remove('d-none');
}

async function buscarClienteGestionPorCelular(celular) {
    if (clienteOriginal && celular === clienteOriginal.celular) {
        resetClienteGestionBusqueda();
        document.getElementById('gestionNombre').value = clienteOriginal.nombre;
        mostrarEstadoClienteGestion('warning', '<i class="ti ti-info-circle"></i> Este celular pertenece al cliente actual de la venta.');
        actualizarEstadoFormularioCliente();
        return;
    }

    mostrarEstadoClienteGestion('muted', '<i class="ti ti-loader ti-spin"></i> Buscando cliente...');

    try {
        const res = await API.post('clientes', {
            action: 'buscar_por_celular',
            phone_customer: celular,
        });

        const nombreInput = document.getElementById('gestionNombre');

        if (res.success && res.found && res.data) {
            clienteGestionBusqueda = {
                encontrado: true,
                id: res.data.id_customer,
                autoNombre: true,
            };
            nombreInput.value = res.data.name_customer || '';
            nombreInput.readOnly = true;
            nombreInput.classList.add('bg-light');
            mostrarEstadoClienteGestion(
                'success',
                `<i class="ti ti-user-check"></i> Cliente encontrado: <strong>${res.data.name_customer}</strong>`
            );
        } else if (res.success) {
            clienteGestionBusqueda = { encontrado: false, id: null, autoNombre: false };
            nombreInput.value = '';
            nombreInput.readOnly = false;
            nombreInput.classList.remove('bg-light');
            nombreInput.focus();
            mostrarEstadoClienteGestion(
                'info',
                '<i class="ti ti-user-plus"></i> Cliente nuevo. Ingresa el nombre para crearlo al guardar.'
            );
        } else {
            resetClienteGestionBusqueda();
            mostrarEstadoClienteGestion('warning', res.message || 'No se pudo buscar el cliente.');
        }
    } catch (e) {
        console.error(e);
        resetClienteGestionBusqueda();
        mostrarEstadoClienteGestion('warning', 'Error al buscar el cliente. Intenta de nuevo.');
    }

    actualizarEstadoFormularioCliente();
}

function onGestionTelefonoInput() {
    const input = document.getElementById('gestionTelefono');
    if (!input) return;

    const val = normalizarCelularGestion(input.value);
    if (input.value !== val) input.value = val;

    if (val.length < 10) {
        if (clienteGestionBusqueda.encontrado || clienteGestionBusqueda.autoNombre) {
            document.getElementById('gestionNombre').value = '';
        }
        resetClienteGestionBusqueda();
    } else if (val.length === 10) {
        buscarClienteGestionPorCelular(val);
        return;
    }

    actualizarEstadoFormularioCliente();
}

function obtenerDatosClienteFormulario() {
    return {
        nombre: document.getElementById('gestionNombre').value.trim(),
        celular: document.getElementById('gestionTelefono').value.replace(/\D/g, ''),
    };
}

function hayCambioCliente() {
    if (!clienteOriginal) return false;
    const actual = obtenerDatosClienteFormulario();
    return actual.nombre !== clienteOriginal.nombre || actual.celular !== clienteOriginal.celular;
}

function actualizarEstadoFormularioCliente() {
    const btn = document.getElementById('btnGuardarCliente');
    const preview = document.getElementById('gestionVistaPrevia');
    const datos = obtenerDatosClienteFormulario();
    const cambio = hayCambioCliente();
    const valido = datos.nombre.length >= 3 && /^\d{10}$/.test(datos.celular);

    if (btn) btn.disabled = !(cambio && valido);

    if (!preview) return;

    if (!cambio) {
        preview.classList.add('d-none');
        return;
    }

    if (!valido) {
        preview.className = 'alert alert-warning border-0 py-2 px-3 small mt-3';
        preview.innerHTML = '<i class="ti ti-alert-circle"></i> Completa un nombre válido y un celular de 10 dígitos.';
        preview.classList.remove('d-none');
        return;
    }

    preview.className = 'alert alert-info border-0 py-2 px-3 small mt-3';
    const esNuevo = !clienteGestionBusqueda.encontrado;
    preview.innerHTML = `
        <strong>Vista previa del cambio:</strong><br>
        <span class="text-muted">De</span> ${clienteOriginal.nombre} (${clienteOriginal.celular})<br>
        <span class="text-muted">A</span> <strong>${datos.nombre}</strong> (${datos.celular})
        ${esNuevo ? '<br><span class="text-primary"><i class="ti ti-user-plus"></i> Se creará un cliente nuevo.</span>' : ''}
    `;
    preview.classList.remove('d-none');
}

function actualizarBotonLiberar() {
    const btn = document.getElementById('btnLiberarNumeros');
    const seleccionados = document.querySelectorAll('.ticket-liberar:checked').length;
    if (btn) btn.disabled = seleccionados === 0;
}

function htmlConfirmacionCambioCliente(datos) {
    return `
        <p class="mb-3">¿Estás seguro de cambiar el cliente de esta venta?</p>
        <div class="border rounded p-2 mb-2 bg-light">
            <div class="text-muted small">Cliente actual</div>
            <div class="fw-semibold">${clienteOriginal.nombre}</div>
            <div class="small">${clienteOriginal.celular}</div>
        </div>
        <div class="text-center my-1"><i class="ti ti-arrow-down"></i></div>
        <div class="border rounded p-2 bg-white">
            <div class="text-muted small">Nuevo cliente</div>
            <div class="fw-semibold text-primary">${datos.nombre}</div>
            <div class="small">${datos.celular}</div>
        </div>
        <p class="small text-muted mt-3 mb-0">
            Se actualizarán <strong>${ventaGestion.quantity_sale} número(s)</strong> de la venta <strong>${ventaGestion.code_sale}</strong>.
            ${!clienteGestionBusqueda.encontrado ? '<br><span class="text-primary">Se registrará un cliente nuevo en el sistema.</span>' : ''}
        </p>
    `;
}

function confirmarCambioCliente() {
    if (!ventaGestion || !clienteOriginal) return;

    const datos = obtenerDatosClienteFormulario();

    if (datos.nombre.length < 3) {
        mostrarGestionFeedback('warning', 'Datos incompletos', 'Ingresa un nombre válido (mínimo 3 caracteres).');
        return;
    }
    if (!/^\d{10}$/.test(datos.celular)) {
        mostrarGestionFeedback('warning', 'Celular inválido', 'El celular debe tener exactamente 10 dígitos.');
        return;
    }
    if (!hayCambioCliente()) {
        mostrarGestionFeedback('info', 'Sin cambios', 'Modifica el nombre o celular para cambiar el cliente.');
        return;
    }

    confirmarAccion({
        titulo: 'Confirmar cambio de cliente',
        html: htmlConfirmacionCambioCliente(datos),
        textoConfirmar: 'Sí, cambiar cliente',
        tipoConfirmar: 'primary',
        onConfirm: () => ejecutarCambioCliente(datos),
    });
}

async function ejecutarCambioCliente(datos) {
    ocultarGestionFeedback();

    try {
        const res = await API.post('ventas', {
            action: 'cambiar_cliente',
            id_sale: ventaGestion.id_sale,
            name_customer: datos.nombre,
            phone_customer: datos.celular,
        });

        if (res.success) {
            const anterior = res.data?.cliente_anterior;
            const nuevo = res.data?.cliente_nuevo;

            mostrarGestionFeedback(
                'success',
                'Cliente cambiado correctamente',
                anterior && nuevo
                    ? `Antes: <strong>${anterior.nombre}</strong> (${anterior.celular}) → Ahora: <strong>${nuevo.nombre}</strong> (${nuevo.celular})`
                    : res.message
            );

            alertify.success('Cliente de la venta actualizado');
            await gestionarVenta(ventaGestion.id_sale, { keepFeedback: true });
            cargarVentas();
        } else {
            mostrarGestionFeedback(
                'error',
                'No se pudo cambiar el cliente',
                res.message || 'Verifica los datos e intenta nuevamente.'
            );
            alertify.error(res.message || 'No se pudo cambiar el cliente');
        }
    } catch (e) {
        console.error(e);
        mostrarGestionFeedback('error', 'Error de conexión', 'No se pudo contactar al servidor. Revisa tu conexión.');
        alertify.error('Error de conexión');
        throw e;
    }
}

async function liberarNumerosSeleccionados() {
    if (!ventaGestion) return;

    const checks = [...document.querySelectorAll('.ticket-liberar:checked')];
    if (!checks.length) {
        mostrarGestionFeedback('warning', 'Nada seleccionado', 'Marca al menos un número para liberar.');
        return;
    }

    if (checks.length >= ventaGestion.tickets.length) {
        mostrarGestionFeedback(
            'warning',
            'No puedes liberar todos',
            'Para dejar la venta sin números usa la opción "Anular venta completa".'
        );
        return;
    }

    const numeros = checks.map(el => el.dataset.numero);
    const ids = checks.map(el => el.value);

    confirmarAccion({
        titulo: 'Confirmar liberación parcial',
        html: `
            <p class="mb-2">¿Liberar <strong>${numeros.length}</strong> número(s) de la venta <strong>${ventaGestion.code_sale}</strong>?</p>
            <div class="d-flex flex-wrap gap-1 mb-2">
                ${numeros.map(n => `<span class="admin-num-chip admin-num-chip--mark">${n}</span>`).join('')}
            </div>
            <p class="small text-muted mb-0">Quedarán disponibles para otra venta. La venta seguirá activa con los números restantes.</p>
        `,
        textoConfirmar: 'Sí, liberar números',
        tipoConfirmar: 'warning',
        onConfirm: () => ejecutarLiberacionNumeros(ids, numeros),
    });
}

async function ejecutarLiberacionNumeros(ids, numeros) {
    ocultarGestionFeedback();

    try {
        const res = await API.post('ventas', {
            action: 'liberar_numeros',
            id_sale: ventaGestion.id_sale,
            tickets_ids: JSON.stringify(ids),
        });

        if (res.success) {
            const liberados = res.data?.numeros_liberados?.join(', ') || numeros.join(', ');
            mostrarGestionFeedback(
                'success',
                'Números liberados correctamente',
                `Liberados: <strong>${liberados}</strong>. Quedan ${res.data?.quantity_restante ?? '?'} número(s) en la venta.`
            );
            alertify.success(res.message);
            await gestionarVenta(ventaGestion.id_sale, { keepFeedback: true });
            cargarVentas();
        } else {
            mostrarGestionFeedback(
                'error',
                'No se pudieron liberar los números',
                res.message || 'Intenta de nuevo o contacta soporte.'
            );
            alertify.error(res.message || 'No se pudieron liberar los números');
        }
    } catch (e) {
        console.error(e);
        mostrarGestionFeedback('error', 'Error de conexión', 'No se pudo completar la operación.');
        alertify.error('Error de conexión');
        throw e;
    }
}

function confirmarAnularVentaCompleta() {
    if (!ventaGestion) return;

    confirmarAccion({
        titulo: 'Anular venta completa',
        html: `
            <p class="mb-2">¿Anular la venta <strong>${ventaGestion.code_sale}</strong>?</p>
            <ul class="small text-muted mb-0 ps-3">
                <li>Se liberarán los <strong>${ventaGestion.quantity_sale}</strong> número(s)</li>
                <li>La venta quedará anulada</li>
                <li>Esta acción <strong>no se puede deshacer</strong></li>
            </ul>
        `,
        textoConfirmar: 'Sí, anular venta',
        tipoConfirmar: 'danger',
        onConfirm: ejecutarAnulacionVenta,
    });
}

async function ejecutarAnulacionVenta() {
    ocultarGestionFeedback();

    try {
        const res = await API.post('ventas', {
            action: 'cancelar_venta',
            id_sale: ventaGestion.id_sale,
        });

        if (res.success) {
            alertify.success(res.message);
            modalGestionInstance?.hide();
            ventaGestion = null;
            clienteOriginal = null;
            cargarVentas();
        } else {
            mostrarGestionFeedback(
                'error',
                'No se pudo anular la venta',
                res.message || 'La venta puede estar ya anulada o no existir.'
            );
            alertify.error(res.message || 'No se pudo anular la venta');
            throw new Error(res.message);
        }
    } catch (e) {
        if (!e.message) {
            mostrarGestionFeedback('error', 'Error de conexión', 'No se pudo contactar al servidor.');
            alertify.error('Error de conexión');
        }
        throw e;
    }
}