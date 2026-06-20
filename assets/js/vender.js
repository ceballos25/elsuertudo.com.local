/**
 * vender.js - Gestión de Ventas El día de TU SUERTE
 * Refactorización Pro: Búsqueda Inteligente, Limpieza de Datos y Soporte Multi-dispositivo.
 * Certificado: Blindaje contra nulos y compatibilidad total.
 */

// --- 1. ESTADO GLOBAL ---
const estado = {
    rifa: null,
    numerosLibres: [],
    carrito: [],
    paginaActual: 1,
    itemsPorPagina: 50,
    config: {}
};

let numerosNotificados = new Set();
let ventaFinalizada = false;
let inventarioInterval = null;

// --- 2. INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    initComponentes();
    cargarRifasActivas();
    asignarEventos();

    // 🔄 refresco automático inventario
    inventarioInterval = setInterval(refrescarNumeros, 5000);

});

async function refrescarNumeros() {
    if (ventaFinalizada) return;
    if (!estado.rifa) return;

    try {
        const fd = new FormData();
        fd.append('action', 'obtener_inventario');
        fd.append('id_raffle', estado.rifa.id);

        const json = await API.post('numeros', fd);
        if (!json.success) return;

        // 🔥 detectar conflictos SIN perder selección válida
        detectarNumerosVendidos(json.data);

        // cache completo
        estado.numerosLibres = json.data;

        // re-render manteniendo selected
        renderizarGrid($('#buscarNumeroInput').val().trim());

    } catch (e) {
        console.error('Error refrescando inventario', e);
    }
}


function detectarNumerosVendidos(nuevoInventario) {
    const removidos = [];

    estado.carrito.forEach(item => {
        const actual = nuevoInventario.find(
            t => String(t.id_ticket) === String(item.id)
        );

        // status 0 = libre
        if (!actual || parseInt(actual.status_ticket) !== 0) {

            // evitar repetir alerta
            if (!numerosNotificados.has(item.id)) {
                removidos.push(item);
                numerosNotificados.add(item.id);
            }
        }
    });

    if (removidos.length === 0) return;

    // quitar solo los conflictivos
    estado.carrito = estado.carrito.filter(
        item => !removidos.some(r => r.id === item.id)
    );

    actualizarCarritoUI();

    mostrarAlertaCritica(
        `El número <strong>${removidos.map(r => r.num).join(', ')}</strong>
         ya no está disponible.<br>`
    );
}


let criticalModalInstance = null;

function mostrarAlertaCritica(mensaje) {
    if (ventaFinalizada) return;
    const modalEl = document.getElementById('criticalModal');
    const msgEl = document.getElementById('criticalModalMsg');

    if (!modalEl || !msgEl) return;

    msgEl.innerHTML = mensaje;

    if (!criticalModalInstance) {
        criticalModalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });
    }

    criticalModalInstance.show();
}


// --- 3. GESTIÓN DE COMPONENTES Y EVENTOS ---

function asignarEventos() {
    $('#selectRifa').on('change', cambiarRifa);

    $('#buscarNumeroInput').on('input', function () {
        estado.paginaActual = 1;
        renderizarGrid(this.value.trim());
    });

    $('#departamento').on('change', function () {
        cargarCiudadesVenta(this.value);
    });

    $('#btnLimpiarCliente').on('click', resetClienteForm);

    /**
     * LÓGICA DE CELULAR: Limpieza (57, espacios) + Búsqueda automática a los 10 dígitos.
     */
    $('#celularCliente').on('input paste', function () {
        let val = $(this).val().replace(/\D/g, ''); // Solo números

        // Quitar el 57 si viene al inicio y el número es largo
        if (val.startsWith('57') && val.length > 10) val = val.substring(2);
        $(this).val(val);

        // DECISIÓN: Búsqueda inteligente al completar el formato celular Colombia (10 dígitos)
        if (val.length === 10) {
            buscarClientePorCelular(val);
        }
    });
}

/**
 * Función que consulta la API para ver si el celular ya existe.
 */
async function buscarClientePorCelular(numero) {
    const fd = new FormData();
    fd.append('action', 'obtener');
    fd.append('search', numero);
    fd.append('status', 1);

    try {
        const json = await API.post('clientes', fd);

        // Si hay resultados y el primer resultado tiene el celular exacto
        if (json.success && json.data && json.data.length > 0) {
            const clienteEncontrado = json.data[0];
            if (clienteEncontrado.phone_customer === numero) {
                llenarFormulario(clienteEncontrado);
                // alertify.success("Cliente reconocido");
            }
        }
    } catch (e) { console.error("Error buscando cliente:", e); }
}

function initComponentes() {
    $('#buscadorCliente').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            url: API.url(),
            type: 'POST', dataType: 'json', delay: 300,
            data: params => {
                let term = params.term ? params.term.trim() : "";

                if (/^[0-9\s+]+$/.test(term)) {
                    let digits = term.replace(/\D/g, '');
                    if (digits.startsWith('57') && digits.length > 10) term = digits.substring(2);
                    else term = digits;
                }

                return { module: 'clientes', action: 'obtener', search: term, status: 1 };
            },
            processResults: res => ({
                results: (res.success && res.data) ? res.data.map(c => ({
                    id: c.id_customer,
                    text: `${c.name_customer} (${c.phone_customer})`,
                    cliente: c
                })) : []
            })
        }
    }).on('select2:select', e => llenarFormulario(e.params.data.cliente))
        .on('select2:unselecting', resetClienteForm);

    $('.select2-ubicacion').select2({ theme: 'bootstrap-5', width: '100%' });

    if (typeof datosColombia !== 'undefined') {
        const $depto = $('#departamento');
        $depto.empty().append('<option value="">Seleccione...</option>');
        Object.keys(datosColombia).sort().forEach(d => $depto.append(new Option(d, d)));
    }
}

// --- 4. LÓGICA DE DATOS (RIFAS) ---

async function cargarRifasActivas() {
    try {
        const json = await API.post('rifas', { action: 'obtener_activas' });
        if (!json.success) return;

        const select = document.getElementById('selectRifa');
        select.innerHTML = '<option value="">Seleccionar Sorteo...</option>';

        json.data.forEach(r => {
            const opt = new Option(r.title_raffle, r.id_raffle);
            opt.dataset.precio = r.price_raffle;
            select.add(opt);
        });

        // ✅ SELECCIONAR RIFA POR DEFECTO (ID = 1)
        const DEFAULT_RAFFLE_ID = '1';

        const existe = [...select.options].some(
            o => o.value === DEFAULT_RAFFLE_ID
        );

        if (existe) {
            select.value = DEFAULT_RAFFLE_ID;

            // 🔥 dispara exactamente la misma lógica
            await cambiarRifa();
        }

    } catch (e) {
        console.error("Error rifas:", e);
    }
}


async function cambiarRifa() {
    const idRifa = $('#selectRifa').val();

    estado.carrito = [];
    estado.numerosLibres = [];
    estado.paginaActual = 1;
    actualizarCarritoUI();

    if (!idRifa) {
        document.getElementById('gridNumeros').innerHTML =
            '<div class="text-center py-5 text-muted w-100">Selecciona un sorteo</div>';
        return;
    }

    const fd = new FormData();
    fd.append('action', 'obtener_inventario');
    fd.append('id_raffle', idRifa);

    try {
        const json = await API.post('numeros', fd);

        if (!json.success) {
            alertify.error(json.message || 'No se pudo cargar inventario');
            return;
        }

        estado.numerosLibres = json.data;

        const opt = document.getElementById('selectRifa').selectedOptions[0];
        estado.rifa = {
            id: idRifa,
            precio: parseFloat(opt.dataset.precio || 0)
        };

        renderizarGrid();

    } catch (e) {
        console.error(e);
        alertify.error("Error cargando números");
    }
}


// --- 5. RENDERIZADO DE INTERFAZ ---

function renderizarGrid(filtro = '') {
    const grid = document.getElementById('gridNumeros');

    let datos = filtro
        ? estado.numerosLibres.filter(t => t.number_ticket.includes(filtro))
        : estado.numerosLibres;

    if (datos.length === 0) {
        grid.innerHTML = '<div class="text-center py-5 text-muted w-100">Sin boletas disponibles</div>';
        return;
    }

    const totalPaginas = Math.max(1, Math.ceil(datos.length / estado.itemsPorPagina));
    if (estado.paginaActual > totalPaginas) estado.paginaActual = totalPaginas;
    if (estado.paginaActual < 1) estado.paginaActual = 1;

    const items = PaginationHelper.getSegment(
        datos,
        estado.paginaActual,
        estado.itemsPorPagina
    );

    grid.innerHTML = '';
    const fragment = document.createDocumentFragment();

    items.forEach(t => {
        const box = document.createElement('div');

        const status = parseInt(t.status_ticket);
        const enCarrito = estado.carrito.some(c => c.id === t.id_ticket);

        let classes = 'number-box m-1';
        let clickable = true;

        if (status === 1) {
            classes += ' sold';
            clickable = false;
        } else if (status === 2) {
            classes += ' sold';
            clickable = false;
        } else if (enCarrito) {
            classes += ' selected';
        }

        box.className = classes;
        box.textContent = t.number_ticket;

        if (clickable) {
            box.onclick = () => {
                toggleCarrito(
                    { id: t.id_ticket, num: t.number_ticket },
                    box
                );
            };
        }

        fragment.appendChild(box);
    });

    grid.appendChild(fragment);

    PaginationHelper.render({
        totalItems: datos.length,
        currentPage: estado.paginaActual,
        limit: estado.itemsPorPagina,
        containerId: 'paginacionContainer',
        callbackName: 'window.cambiarPaginaGrid'
    });
}



function toggleCarrito(tObj, btn = null) {

    const ticket = estado.numerosLibres.find(
        t => String(t.id_ticket) === String(tObj.id)
    );

    if (!ticket || parseInt(ticket.status_ticket) !== 0) {
        mostrarAlertaCritica(
            `El número <strong>${tObj.num}</strong> ya no está disponible.`
        );
        return;
    }

    const idx = estado.carrito.findIndex(item => item.id === tObj.id);
    if (idx === -1) estado.carrito.push(tObj);
    else estado.carrito.splice(idx, 1);

    if (btn) btn.classList.toggle('selected');
    else renderizarGrid($('#buscarNumeroInput').val());

    actualizarCarritoUI();
}


function actualizarCarritoUI() {
    const total = estado.carrito.length * (estado.rifa?.precio || 0);
    const fmt = n =>
        new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(n);

    // Totales
    $('#lblTotalDesktop, #lblTotalMobile').text(fmt(total));
    $('#lblCantidadMobile, #lblCantidadDesktop').text(estado.carrito.length);

    // 🔥 RESUMEN CON MISMO ESTILO DEL FRONT
    const listaHtml = estado.carrito.length === 0
        ? `
            <li class="list-group-item text-center text-muted py-4 border-0 small">
                Selecciona tus números
            </li>
        `
        : estado.carrito.map(t => `
            <li class="list-group-item border-0 px-0">
                <div class="d-flex align-items-center justify-content-between">
                    
                    <!-- 🔹 MISMA CLASE number-box selected -->
                    <div class="number-box selected me-2" style="width:60px">
                        ${t.num}
                    </div>

                    <div class="flex-grow-1 text-end">
                        <div class="fw-bold text-success small">
                            ${fmt(estado.rifa.precio)}
                        </div>
                        <button
                            class="btn btn-sm text-danger border-0 mt-1"
                            onclick="window.removerItem(${t.id})"
                            title="Quitar número">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>

                </div>
            </li>
        `).join('');

    $('#listaCarritoDesktop, #listaCarritoMobile').html(listaHtml);
}


// --- 6. PROCESAMIENTO DE VENTA ---

function restaurarBotonesVenta(botonesAccion) {
    botonesAccion.forEach(b => {
        b.disabled = false;
        b.innerHTML = b.id === 'btnCompletarVenta' ? 'COMPLETAR PAGO' : 'COMPLETAR';
    });
}

function procesarVenta() {
    const btnD = document.getElementById('btnCompletarVenta');
    const btnM = document.querySelector('.btn-completar-mobile');
    const botonesAccion = [btnD, btnM].filter(b => b !== null);

    const cliente = {
        id: $('#idCliente').val(),
        nombre: $('#nombreCliente').val().trim(),
        celular: $('#celularCliente').val().trim(),
    };

    if (estado.carrito.length === 0) return alertify.error('Debes seleccionar al menos un boleto.');
    if (!cliente.nombre || !cliente.celular) {
        return alertify.error('Nombre y celular son obligatorios.');
    }
    if (botonesAccion.some(b => b.disabled)) return;

    const cantidad = estado.carrito.length;
    const total = cantidad * estado.rifa.precio;

    if (typeof confirmarAccion !== 'function') {
        alertify.error('No se pudo abrir la confirmación de venta');
        return;
    }

    confirmarAccion({
        titulo: 'Confirmar venta',
        html: `<p class="text-muted mb-0">Registrar <strong>${cantidad}</strong> número(s) para <strong>${cliente.nombre}</strong> por <strong>$${total.toLocaleString('es-CO')}</strong>.</p>`,
        textoConfirmar: 'Confirmar venta',
        tipoConfirmar: 'success',
        onConfirm: async () => {
            const codigoVenta =
                'VM-' +
                Math.floor(Date.now() / 1000).toString(36).toUpperCase() +
                Math.random().toString(16).substr(2, 2).toUpperCase();

            botonesAccion.forEach(b => {
                b.disabled = true;
                b.innerHTML = b.id === 'btnCompletarVenta'
                    ? '<span class="spinner-border spinner-border-sm"></span>...'
                    : '...';
            });

            if (typeof showPreloader === 'function') showPreloader();

            const fd = new FormData();
            fd.append('action', 'crear_venta');
            fd.append('code_sale', codigoVenta);
            fd.append('quantity_sale', cantidad);
            fd.append('id_customer', cliente.id);
            fd.append('id_raffle', estado.rifa.id);
            fd.append('total_sale', total);
            fd.append('tickets_ids', JSON.stringify(estado.carrito.map(t => t.id)));
            fd.append('name_customer', cliente.nombre);
            fd.append('phone_customer', cliente.celular);

            try {
                const json = await API.post('ventas', fd);

                if (!json.success) {
                    alertify.error(json.message || 'No se pudo registrar la venta');
                    restaurarBotonesVenta(botonesAccion);
                    throw new Error(json.message || 'sale_failed');
                }

                ventaFinalizada = true;

                if (inventarioInterval) {
                    clearInterval(inventarioInterval);
                    inventarioInterval = null;
                }

                estado.carrito = [];
                numerosNotificados.clear();

                await generarReciboFinal(json.id_sale);
            } catch (e) {
                if (!e?.message || e.message === 'sale_failed') {
                    // error ya mostrado
                } else {
                    alertify.error('Error en el servidor');
                    restaurarBotonesVenta(botonesAccion);
                }
                throw e;
            } finally {
                if (typeof hidePreloader === 'function') hidePreloader();
            }
        },
    });
}

async function generarReciboFinal(idVenta) {
    const fd = new FormData();
    fd.append('action', 'detalle_venta');
    fd.append('id_sale', idVenta);

    try {
        const json = await API.post('ventas', fd);

    if (json.success) {
        $('.fixed-bottom').addClass('d-none');

        const container = document.querySelector('.body-wrapper-inner');
        container.innerHTML = `
            <div class="container py-5 animated fadeIn">
                ${json.html_recibo}
                <div class="mt-4 text-center no-print">
                    <button class="btn btn-dark fw-bold px-5 rounded-pill shadow" onclick="location.reload()">NUEVA VENTA</button>
                </div>
            </div>`;

        // 🔥 APLICAR SETTINGS AL RECIBO YA INSERTADO
        cargarSettingsPublic();

        window.scrollTo(0, 0);
    }

    } catch (e) {
        alertify.error("Error visual al cargar el recibo.");
        setTimeout(() => location.reload(), 3000);
    }
}

// --- 7. HELPERS Y FORMULARIOS ---

window.cambiarPaginaGrid = (page) => {
    const filtro = $('#buscarNumeroInput').val().trim();
    const datos = filtro
        ? estado.numerosLibres.filter(t => t.number_ticket.includes(filtro))
        : estado.numerosLibres;
    const totalPaginas = Math.max(1, Math.ceil(datos.length / estado.itemsPorPagina));
    estado.paginaActual = Math.max(1, Math.min(page, totalPaginas));
    renderizarGrid(filtro);
};

window.removerItem = (id) => toggleCarrito({ id: id });
window.procesarVentaMobile = () => procesarVenta();

function llenarFormulario(c) {
    $('#idCliente').val(c.id_customer);
    $('#nombreCliente').val(c.name_customer);    
    $('#celularCliente').val(c.phone_customer);
    $('#btnLimpiarCliente').removeClass('d-none');
    toggleInputs(true);
}

function resetClienteForm() {
    $('#idCliente').val('');
    document.getElementById('formClienteVenta').reset();
    $('#buscadorCliente').val(null).trigger('change');
    $('#btnLimpiarCliente').addClass('d-none');
    toggleInputs(false);
}

function toggleInputs(bloquear) { $('#formClienteVenta input:not([type="hidden"])').prop('readonly', bloquear); }


window.seleccionarAlAzar = () => {

    const disponibles = estado.numerosLibres.filter(t =>
        parseInt(t.status_ticket) === 0 &&
        !estado.carrito.some(c => c.id === t.id_ticket)
    );

    if (disponibles.length === 0) {
        alertify.error("No hay números disponibles para seleccionar.");
        return;
    }

    const random = disponibles[
        Math.floor(Math.random() * disponibles.length)
    ];

    toggleCarrito({
        id: random.id_ticket,
        num: random.number_ticket
    });
};

/* ======================================================
   SETTINGS PÚBLICOS (api.php → module settings)
====================================================== */

let SETTINGS = {};

async function cargarSettingsPublic() {
    try {
        const json = await API.post('settings', { action: 'obtener' });
        if (!json.success || !Array.isArray(json.data)) return;

        json.data.forEach(s => {
            SETTINGS[s.key_setting] = s.value_setting;
        });

        aplicarSettingsEnRecibo();

    } catch (e) {
        console.error('Error cargando settings públicos', e);
    }
}

/**
 * Aplica los settings SOLO si existen los elementos
 * (esto corre cuando ya se muestra el recibo)
 */
function aplicarSettingsEnRecibo() {

    const wa = document.getElementById('linkWhatsapp');
    if (wa && SETTINGS.whatsapp_line_main) {
        wa.href = `https://wa.me/${SETTINGS.whatsapp_line_main}`;
    }

    const ig = document.getElementById('linkInstagram');
    if (ig && SETTINGS.instagram_url) {
        ig.href = SETTINGS.instagram_url;
    }

    const fb = document.getElementById('linkFacebook');
    if (fb && SETTINGS.facebook_url) {
        fb.href = SETTINGS.facebook_url;
    }

    const tk = document.getElementById('linkTiktok');
    if (tk && SETTINGS.tiktok_url) {
        tk.href = SETTINGS.tiktok_url;
    }
}

