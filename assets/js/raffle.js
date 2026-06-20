function getRaffleIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    const env = params.get('env');

    if (!env) return null;

    const id = parseInt(env, 10);
    return Number.isInteger(id) && id > 0 ? id : null;
}

/**
 * numeros.public.js
 * VERSIÓN FINAL CON AUTO-REFRESH EN PAGINACIÓN
 */

const ID_RAFFLE = getRaffleIdFromURL();

function escHtml(str) {
    if (str == null) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

function getThemeColor(varName, fallback = '') {
    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(varName)
        .trim();
    return value || fallback;
}

let paginaActual = 1;
const itemsPorPagina = 100;
let PRECIO_BOLETA = 0; 

let SETTINGS = {
    whatsapp_line_main: '',
    whatsapp_line_support: '',
    facebook_url: '',
    instagram_url: '',
    site_active: '1'
};

let cache = [];
let selectedTickets = new Map(); // id_ticket => number_ticket
let RAFFLE_ACTIVE = true;
let RAFFLE_IS_FREE = false;
let MAX_PUBLIC_TICKETS = 50;

/* ================= INIT ================= */

document.addEventListener('DOMContentLoaded', () => {
    if (!ID_RAFFLE) {
        mostrarAlertaUI({
            type: 'warning',
            title: 'Rifa no especificada',
            message: 'Ponte en contacto con el administrador para obtener el enlace de la rifa.',
            blocking: true
        });
        return;
    }

    cargarSettings();
    cargarRifaPorId();
    cargarNumeros();
    initCheckoutForm();

    const grid = document.getElementById('numbersGrid');
    if (grid) {
        grid.addEventListener('click', e => {
            const box = e.target.closest('.number-box');
            if (!box) return;

            const id = Number(box.dataset.id);
            const number = box.dataset.number;
            const status = Number(box.dataset.status);

            if (!RAFFLE_ACTIVE) return;

            if (status === 0) {
                toggleSeleccion(id, number, box);
            } else {
                verInfoTicket(id);
            }
        });
    }
});

/* ================= PAGINACIÓN ================= */

window.cambiarPaginaPublica = async function(n) {
    const totalPaginas = Math.max(1, Math.ceil(cache.length / itemsPorPagina));
    paginaActual = Math.max(1, Math.min(n, totalPaginas));
    
    // 🔥 ACTUALIZAR DATOS ANTES DE CAMBIAR PÁGINA
    await cargarNumeros();
    
    // Scroll suave al inicio de la rifa
    const gridEl = document.getElementById('numbersGrid');
    if (gridEl) {
        gridEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

/* ================= DATA ================= */

async function cargarSettings() {
    try {
        const j = await API.post('settings', { action: 'obtener' });
        if (!j.success || !Array.isArray(j.data)) return;

        j.data.forEach(item => {
            const key = item.key_setting;
            const value = item.value_setting || '';

            switch (key) {
                case 'whatsapp_line_main':
                    SETTINGS.whatsapp_line_main = value.replace(/\D/g, '');
                    break;
                case 'whatsapp_line_support':
                    SETTINGS.whatsapp_line_support = value.replace(/\D/g, '');
                    break;
                case 'facebook_url':
                    SETTINGS.facebook_url = value;
                    break;
                case 'instagram_url':
                    SETTINGS.instagram_url = value;
                    break;
                case 'site_active':
                    SETTINGS.site_active = value;
                    break;
            }
        });

        aplicarSettingsUI();
        if (SETTINGS.site_active !== '1') {
            mostrarMantenimiento();
        }

    } catch (e) {
        console.error('Error cargando settings', e);
    }
}

async function cargarRifaActiva() {
    try {
        const j = await API.post('rifas', { action: 'obtener_activas' });

        if (j.success && j.data.length > 0) {
            renderRifa(j.data[0]);
        }
    } catch (e) {
        console.error('Error cargando rifa', e);
    }
}

async function cargarRifaPorId() {
    if (!ID_RAFFLE) {
        console.error('ID_RAFFLE no definido');
        return;
    }

    try {
        const j = await API.post('rifas', { action: 'obtener_por_id', id_raffle: ID_RAFFLE });

        if (j.success && j.data) {
            renderRifa(j.data);
        } else {
            mostrarAlertaUI({
                type: 'error',
                title: 'Rifa no encontrada',
                message: 'La rifa solicitada no existe o no está activa.',
                blocking: true
            });
        }
    } catch (e) {
        console.error('Error cargando rifa por ID', e);
    }
}

async function cargarNumeros() {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener_inventario');
        fd.append('id_raffle', ID_RAFFLE);

        const j = await API.post('numeros', fd);
        const nuevoCache = j.success ? j.data : [];

        detectarVendidos(nuevoCache);
        cache = nuevoCache;
        renderGrid();
        actualizarDisponibles();
    } catch (e) {
        console.error('Error cargando números', e);
    }
}

/* ================= DETECTAR VENDIDOS ================= */

function detectarVendidos(nuevoCache) {
    const removidos = [];
    
    selectedTickets.forEach((numeroTicket, idTicket) => {
        const ticketEnServidor = nuevoCache.find(x => Number(x.id_ticket) === Number(idTicket));
        
        if (!ticketEnServidor || Number(ticketEnServidor.status_ticket) !== 0) {
            removidos.push(numeroTicket);
            selectedTickets.delete(idTicket);
        }
    });
    
    if (removidos.length > 0) {
        updateCheckoutUI(); 
        
        const modalEl = document.getElementById('checkoutModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
        
        mostrarAlertaUI({
            type: 'error',
            title: '¡Números no disponibles!',
            message: `Los siguientes números ya no están disponibles:<br><strong>${removidos.join(', ')}</strong><br><br>Hemos actualizado tu carrito.`,
            blocking: true
        });
    }
}

/* ================= RENDER GRID ================= */

function renderGrid() {
    const grid = document.getElementById('numbersGrid');
    if (!grid) return;

    grid.innerHTML = '';

    const totalPaginas = Math.max(1, Math.ceil(cache.length / itemsPorPagina));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;

    const items = PaginationHelper.getSegment(
        cache,
        paginaActual,
        itemsPorPagina
    );

    const fragment = document.createDocumentFragment();

    for (const t of items) {
        const col = document.createElement('div');
        col.className = 'col-3 col-md-2 col-lg-1 px-1 mb-2'; 

        const div = document.createElement('div');
        div.className = 'number-box';

        div.dataset.id = t.id_ticket;
        div.dataset.number = t.number_ticket;
        div.dataset.status = t.status_ticket;

        if (Number(t.status_ticket) === 0) {
            div.classList.add('free');
            if (selectedTickets.has(Number(t.id_ticket))) {
                div.classList.add('selected');
            }
            div.innerHTML = `<span class="number-box__num">${escHtml(t.number_ticket)}</span>`;
        } else {
            div.classList.add(Number(t.status_ticket) === 2 ? 'reserved' : 'sold');

            const nombre = t.name_customer ? String(t.name_customer).trim() : '';
            const corto = nombre
                ? nombre.split(' ')[0].substring(0, 12)
                : (Number(t.status_ticket) === 2 ? 'Reservado' : 'Vendido');

            div.innerHTML = `
                <span class="number-box__num">${escHtml(t.number_ticket)}</span>
                <span class="number-box__owner">${escHtml(corto)}</span>
                <i class="bi bi-eye number-box__detail" aria-hidden="true"></i>
            `;
        }


        col.appendChild(div);
        fragment.appendChild(col);
    }

    grid.appendChild(fragment);

    PaginationHelper.render({
        totalItems: cache.length,
        currentPage: paginaActual,
        limit: itemsPorPagina,
        containerId: 'paginacionContainer',
        callbackName: 'window.cambiarPaginaPublica'
    });
}

/* ================= VER INFO TICKET ================= */

async function verInfoTicket(idTicket) {
    const modalEl = document.getElementById('ticketInfoModal');
    const instance = new bootstrap.Modal(modalEl);
    
    const avatarEl = document.getElementById('ticketAvatar');
    const nameEl = document.getElementById('ticketClientName');
    
    if (avatarEl) {
        avatarEl.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"></div>';
        avatarEl.style.backgroundColor = '#ccc';
    }
    if (nameEl) nameEl.textContent = 'Cargando...';
    
    instance.show();

    const fd = new FormData();
    fd.append('action', 'obtener_info_ticket');
    fd.append('id_ticket', idTicket);

    try {
        const j = await API.post('numeros', fd);

        if (!j.success) {
            instance.hide();
            mostrarAlertaUI({
                type: 'warning',
                title: 'Información no disponible',
                message: j.message || 'No se pudo obtener la información'
            });
            return;
        }

        const data = j.data;
        const dateEl = document.getElementById('ticketDate');
        const statusEl = document.getElementById('ticketStatus');

        const nombre = data.name_customer || (data.tipo === 'reservado' ? 'Reservado' : 'Vendido');
        const inicial = nombre.charAt(0).toUpperCase();

        if (avatarEl) {
            avatarEl.textContent = inicial; 
            avatarEl.style.backgroundColor = getAvatarColor(inicial);
        }
        if (nameEl) nameEl.textContent = nombre;

        if (statusEl) {
            statusEl.className = 'badge rounded-pill px-3 py-2 ticket-status';
            if (data.tipo === 'reservado') {
                statusEl.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Reservado';
                statusEl.classList.add('reservado');
            } else {
                statusEl.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Vendido';
                statusEl.classList.add('vendido');
            }
        }

        if (dateEl) {
            // const fecha = new Date(data.datetime);
            // // 🕐 RESTAR 5 HORAS
            // fecha.setHours(fecha.getHours() - 5);
            
            // dateEl.textContent = fecha.toLocaleString('es-CO', {
            //     day: 'numeric', month: 'long', year: 'numeric',
            //     hour: '2-digit', minute: '2-digit'
            // });
            
            const fecha = new Date(data.datetime);

            dateEl.textContent = fecha.toLocaleString('es-CO', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

    } catch (e) {
        console.error(e);
        instance.hide();
    }
}

function getAvatarColor(letter) {
    const primary = getThemeColor('--raffle-color-primary', '#198754');
    const primaryLight = getThemeColor('--raffle-color-primary-light', '#20c997');
    const colors = [primary, '#0d6efd', '#6f42c1', '#d63384', '#fd7e14', primaryLight];
    return colors[letter.charCodeAt(0) % colors.length];
}

/* ================= SELECCIÓN ================= */

function toggleSeleccion(id, number, el = null) {
    if (!el) {
        el = document.querySelector(`.number-box[data-id="${id}"]`);
    }

    if (selectedTickets.has(id)) {
        selectedTickets.delete(id);
        if (el) el.classList.remove('selected');
    } else {
        if (RAFFLE_IS_FREE && selectedTickets.size >= MAX_PUBLIC_TICKETS) {
            selectedTickets.clear();
            document.querySelectorAll('.number-box.selected').forEach(box => box.classList.remove('selected'));
        }

        if (!RAFFLE_IS_FREE && selectedTickets.size >= MAX_PUBLIC_TICKETS) {
            mostrarAlertaUI({
                type: 'warning',
                title: 'Límite alcanzado',
                message: `Máximo ${MAX_PUBLIC_TICKETS} números por pedido.`
            });
            return;
        }

        selectedTickets.set(id, String(number));
        if (el) el.classList.add('selected');
    }

    updateCheckoutUI();
}

/* ================= CHECKOUT UI ================= */

function updateCheckoutUI() {
    const cantidad = selectedTickets.size;
    const total = cantidad * PRECIO_BOLETA;
    
    const numerosTexto = Array.from(selectedTickets.values()).join(', ') || '---';

    setText('checkoutQty', cantidad);
    setText('checkoutTotal', total);
    setText('checkoutQtyMobile', cantidad);
    setText('checkoutTotalMobile', total);
    setText('modalNumbers', numerosTexto);
    setText('modalTotal', total);

    const mobileBar = document.getElementById('mobileCheckout');
    if (mobileBar) {
        if (cantidad > 0) {
            mobileBar.classList.add('translate-show');
        } else {
            mobileBar.classList.remove('translate-show');
        }
    }

    document.body.classList.toggle('mobile-checkout-open', cantidad > 0);
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (!el) return;

    el.innerText = typeof value === 'number'
        ? value.toLocaleString('es-CO')
        : value;
}

/* ================= CONTADORES ================= */

function actualizarDisponibles() {
    const total = cache.length;
    const disponibles = cache.filter(t => Number(t.status_ticket) === 0).length;
    const vendidos = total - disponibles;
    const pctVendido = total > 0 ? Math.round((vendidos / total) * 100) : 0;

    setText('availableCount', disponibles);
    setText('totalCount', total);
    setText('availabilityPercent', `${pctVendido}% vendido`);

    const bar = document.getElementById('availabilityProgress');
    if (bar) {
        bar.style.width = `${pctVendido}%`;
        bar.setAttribute('aria-valuenow', String(pctVendido));
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
    }
}

/* ================= CHECKOUT — CLIENTE POR CELULAR ================= */

let checkoutClienteBusqueda = { encontrado: false, id: null, autoNombre: false };

function initCheckoutForm() {
    const phoneInput = document.getElementById('clientPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', onCheckoutPhoneInput);
    }

    const modalEl = document.getElementById('checkoutModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', resetCheckoutForm);
    }
}

function normalizarCelularCheckout(val) {
    return String(val || '').replace(/\D/g, '').slice(0, 10);
}

function resetCheckoutClienteBusqueda() {
    checkoutClienteBusqueda = { encontrado: false, id: null, autoNombre: false };
    const nameInput = document.getElementById('clientName');
    if (nameInput) {
        nameInput.readOnly = false;
        nameInput.classList.remove('bg-light');
    }
}

function mostrarEstadoCheckoutCliente(tipo, mensaje) {
    const el = document.getElementById('clientPhoneStatus');
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

async function buscarClienteCheckoutPorCelular(celular) {
    mostrarEstadoCheckoutCliente('muted', '<i class="bi bi-arrow-repeat"></i> Buscando cliente...');

    try {
        const res = await API.post('clientes', {
            action: 'buscar_por_celular',
            phone_customer: celular,
        });

        const nameInput = document.getElementById('clientName');

        if (res.success && res.found && res.data) {
            checkoutClienteBusqueda = {
                encontrado: true,
                id: res.data.id_customer,
                autoNombre: true,
            };
            if (nameInput) {
                nameInput.value = res.data.name_customer || '';
                nameInput.readOnly = true;
                nameInput.classList.add('bg-light');
            }
            mostrarEstadoCheckoutCliente(null, '');
        } else if (res.success) {
            checkoutClienteBusqueda = { encontrado: false, id: null, autoNombre: false };
            if (nameInput) {
                nameInput.value = '';
                nameInput.readOnly = false;
                nameInput.classList.remove('bg-light');
                nameInput.focus();
            }
            mostrarEstadoCheckoutCliente(null, '');
        } else {
            resetCheckoutClienteBusqueda();
            mostrarEstadoCheckoutCliente('warning', escHtml(res.message || 'No se pudo buscar el cliente.'));
        }
    } catch (e) {
        console.error(e);
        resetCheckoutClienteBusqueda();
        mostrarEstadoCheckoutCliente('warning', 'Error al buscar el cliente. Intenta de nuevo.');
    }
}

function onCheckoutPhoneInput() {
    const input = document.getElementById('clientPhone');
    if (!input) return;

    const val = normalizarCelularCheckout(input.value);
    if (input.value !== val) input.value = val;

    if (val.length < 10) {
        if (checkoutClienteBusqueda.encontrado || checkoutClienteBusqueda.autoNombre) {
            const nameInput = document.getElementById('clientName');
            if (nameInput) nameInput.value = '';
        }
        resetCheckoutClienteBusqueda();
        mostrarEstadoCheckoutCliente(null, '');
        return;
    }

    if (val.length === 10) {
        buscarClienteCheckoutPorCelular(val);
    }
}

function resetCheckoutForm() {
    resetCheckoutClienteBusqueda();
    mostrarEstadoCheckoutCliente(null, '');

    const phoneInput = document.getElementById('clientPhone');
    const nameInput = document.getElementById('clientName');
    if (phoneInput) phoneInput.value = '';
    if (nameInput) {
        nameInput.value = '';
        nameInput.readOnly = false;
        nameInput.classList.remove('bg-light');
    }

    resetBotonConfirmar();
}

/* ================= CONFIRMAR RESERVA ================= */

function resetBotonConfirmar() {
    const btn = document.querySelector('#checkoutModal button.btn-success');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = RAFFLE_IS_FREE
            ? '<i class="bi bi-check-circle me-1"></i>Registrar'
            : '<i class="bi bi-check-circle me-1"></i>Confirmar';
    }
}

async function confirmarReserva() {
    if (!RAFFLE_ACTIVE) {
        mostrarRifaDetenida();
        return;
    }
    const btn = document.querySelector('#checkoutModal button.btn-success');

    if (btn && btn.disabled) return;

    let celular = (document.getElementById('clientPhone') || {}).value || '';
    celular = celular.replace(/\D/g, '');

    if (!/^\d{10}$/.test(celular)) {
        mostrarAlertaUI({
            type: 'warning',
            title: 'Celular inválido',
            message: 'Ingresa un número de celular de <strong>10 dígitos</strong>.'
        });
        return;
    }

    let nombre = (document.getElementById('clientName') || {}).value || '';
    nombre = nombre.trim().replace(/\s+/g, ' ').replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '');

    if (nombre.length < 4) {
        mostrarAlertaUI({
            type: 'warning',
            title: 'Nombre inválido',
            message: 'Ingresa un <strong>nombre válido</strong>.'
        });
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Procesando...';
    }

    const idsTickets = Array.from(selectedTickets.keys());

    if (RAFFLE_IS_FREE) {
        await confirmarParticipacionGratis(idsTickets, nombre, celular, btn);
        return;
    }

    const phone = SETTINGS.whatsapp_line_main || SETTINGS.whatsapp_line_support;
    const numerosVisuales = Array.from(selectedTickets.values()).join(', ');
    const total = selectedTickets.size * PRECIO_BOLETA;

    const fd = new FormData();
    fd.append('action', 'crear_reserva');
    fd.append('id_raffle', ID_RAFFLE);
    fd.append('name_customer', nombre);
    fd.append('phone_customer', celular);
    fd.append('tickets', JSON.stringify(idsTickets));

    try {
        const j = await API.post('reservations', fd);

        if (!j.success) {
            if (Array.isArray(j.unavailable) && j.unavailable.length > 0) {
                const numerosVendidos = [];

                j.unavailable.forEach(id => {
                    const idNum = Number(id);
                    const numVisual = selectedTickets.get(idNum);
                    if (numVisual) numerosVendidos.push(numVisual);
                    selectedTickets.delete(idNum);
                });

                updateCheckoutUI();
                await cargarNumeros();

                bootstrap.Modal
                    .getInstance(document.getElementById('checkoutModal'))
                    ?.hide();

                mostrarAlertaUI({
                    type: 'error',
                    title: 'Números no disponibles',
                    message: `Los siguientes números ya fueron vendidos:<br><strong>${numerosVendidos.join(', ')}</strong><br><br>Tu carrito fue actualizado.`,
                    blocking: true
                });
            } else {
                mostrarAlertaUI({
                    type: 'error',
                    title: 'Error',
                    message: j.message || 'Ocurrió un error.'
                });
            }

            resetBotonConfirmar();
            return;
        }

        const texto =
            "Hola 👋 quiero confirmar mi compra:\n\n" +
            "🔢 Cantidad: " + idsTickets.length + "\n" +
            "🎟 Números: " + numerosVisuales + "\n" +
            "💰 Total: $" + total.toLocaleString('es-CO') + "\n\n" +
            "👤 Cliente: " + nombre + "\n" +
            "📱 Celular: " + celular + "\n\n" +
            "🧾 Código: " + j.token;

        if (!phone || phone.length < 10) {
            mostrarAlertaUI({
                type: 'success',
                title: 'Reserva creada',
                message: 'Tu código es <strong>' + escHtml(j.token) + '</strong>. Contacta al organizador para confirmar el pago.',
                blocking: true
            });
        } else {
            abrirWhatsApp(phone, texto);
        }

        bootstrap.Modal
            .getInstance(document.getElementById('checkoutModal'))
            ?.hide();

        selectedTickets.clear();
        updateCheckoutUI();
        await cargarNumeros();
        resetBotonConfirmar();

    } catch (err) {
        console.error(err);
        mostrarAlertaUI({
            type: 'error',
            title: 'Error de conexión',
            message: 'Intenta nuevamente.'
        });
        resetBotonConfirmar();
    }
}

async function confirmarParticipacionGratis(idsTickets, nombre, celular, btn) {
    const fd = new FormData();
    fd.append('action', 'registrar_gratis');
    fd.append('id_raffle', ID_RAFFLE);
    fd.append('name_customer', nombre);
    fd.append('phone_customer', celular);
    fd.append('tickets', JSON.stringify(idsTickets));

    try {
        const j = await API.post('participacion', fd);

        if (!j.success) {
            if (Array.isArray(j.unavailable) && j.unavailable.length > 0) {
                j.unavailable.forEach(id => selectedTickets.delete(Number(id)));
                updateCheckoutUI();
                await cargarNumeros();
            }

            bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();

            mostrarAlertaUI({
                type: j.existing ? 'info' : 'error',
                title: j.existing ? 'Ya participaste' : 'No se pudo registrar',
                message: escHtml(j.message || 'Ocurrió un error.'),
                blocking: true
            });
            resetBotonConfirmar();
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('checkoutModal'))?.hide();

        const numeros = Array.isArray(j.numbers) ? j.numbers.join(', ') : Array.from(selectedTickets.values()).join(', ');

        mostrarAlertaUI({
            type: 'success',
            title: '¡Número registrado!',
            message:
                `Tu número <strong>${escHtml(numeros)}</strong> quedó confirmado.` +
                (j.code_sale ? `<br><small class="text-muted">Código: ${escHtml(j.code_sale)}</small>` : ''),
            blocking: true
        });

        selectedTickets.clear();
        updateCheckoutUI();
        await cargarNumeros();
        resetBotonConfirmar();
    } catch (err) {
        console.error(err);
        mostrarAlertaUI({
            type: 'error',
            title: 'Error de conexión',
            message: 'Intenta nuevamente.'
        });
        resetBotonConfirmar();
    }
}
/**
 * Función dedicada para abrir WhatsApp de forma confiable
 * Usa múltiples estrategias para máxima compatibilidad
 */
/* ================= HELPERS UI ================= */
function abrirWhatsApp(phone, texto) {
    const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(texto)}`;

    const win = window.open(url, '_blank');

    if (!win || win.closed || typeof win.closed === 'undefined') {
        window.location.href = url;
    }
}


function aplicarSettingsUI() {
    const phone = SETTINGS.whatsapp_line_main || SETTINGS.whatsapp_line_support;
    if (SETTINGS.facebook_url) document.querySelectorAll('[data-facebook]').forEach(el => { el.href = SETTINGS.facebook_url; el.classList.remove('d-none'); });
    if (SETTINGS.instagram_url) document.querySelectorAll('[data-instagram]').forEach(el => { el.href = SETTINGS.instagram_url; el.classList.remove('d-none'); });
    if (phone) document.querySelectorAll('[data-whatsapp]').forEach(el => { el.href = `https://api.whatsapp.com/send?phone=${phone}`; el.classList.remove('d-none'); });
}

function renderRifa(raffle) {
    RAFFLE_IS_FREE = Number(raffle.is_free_raffle) === 1;
    MAX_PUBLIC_TICKETS = RAFFLE_IS_FREE ? 1 : 50;
    PRECIO_BOLETA = RAFFLE_IS_FREE ? 0 : (Number(raffle.price_raffle) || 0);
    RAFFLE_ACTIVE = Number(raffle.status_raffle) === 1;

    setText('raffleTitle', raffle.title_raffle);
    setText('premioLoteria', raffle.description_raffle);
    setText('rafflePrice', RAFFLE_IS_FREE ? 'Gratis' : PRECIO_BOLETA.toLocaleString('es-CO'));
    setText('raffleDigits', `${raffle.digits_raffle} cifras`);
    applyFreeModeUI();
    
    const fecha = new Date(raffle.date_raffle);
    setText('raffleDate', fecha.toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }));
    
    const infoSkeleton = document.getElementById('infoSkeleton');
    const infoReal = document.querySelector('.card-body .real-content');
    if(infoSkeleton) infoSkeleton.classList.add('d-none');
    if(infoReal) infoReal.classList.remove('d-none');

    const premiosSkeleton = document.getElementById('premiosSkeleton');
    const premiosReal = document.getElementById('premiosWrapper');
    if(premiosSkeleton) premiosSkeleton.classList.add('d-none');
    if(premiosReal) premiosReal.classList.remove('d-none');

    renderPremios(raffle.promotions_raffle);

    if (RAFFLE_ACTIVE) {
        ocultarRifaDetenida();
    } else {
        mostrarRifaDetenida();
    }
}

function applyFreeModeUI() {
    const priceBadge = document.querySelector('#rafflePrice')?.closest('.badge');
    if (priceBadge) {
        priceBadge.classList.toggle('bg-success', !RAFFLE_IS_FREE);
        priceBadge.classList.toggle('bg-info', RAFFLE_IS_FREE);
        const icon = priceBadge.querySelector('i');
        if (icon) {
            icon.className = RAFFLE_IS_FREE
                ? 'bi bi-gift me-1'
                : 'bi bi-currency-dollar me-1';
        }
    }

    document.querySelectorAll('.js-checkout-total-block').forEach(el => {
        el.classList.toggle('d-none', RAFFLE_IS_FREE);
    });

    const primaryBtn = document.getElementById('landingCheckoutBtn');
    if (primaryBtn) {
        primaryBtn.innerHTML = RAFFLE_IS_FREE
            ? '<i class="bi bi-check-circle me-2"></i>Confirmar número'
            : '<i class="bi bi-credit-card me-2"></i>Pagar ahora';
    }

    const mobileBtn = document.getElementById('landingCheckoutBtnMobile');
    if (mobileBtn) {
        mobileBtn.textContent = RAFFLE_IS_FREE ? 'Confirmar' : 'Pagar';
    }

    const note = document.getElementById('landingCheckoutNote');
    if (note) {
        note.innerHTML = RAFFLE_IS_FREE
            ? '<i class="bi bi-person-check me-1"></i>1 número por persona · Confirmación inmediata'
            : '<i class="bi bi-shield-check me-1"></i>Reserva segura vía WhatsApp';
    }

    const modalTitle = document.getElementById('checkoutModalTitle');
    if (modalTitle) {
        modalTitle.innerHTML = RAFFLE_IS_FREE
            ? '<i class="bi bi-gift text-success me-2"></i>Confirmar participación'
            : '<i class="bi bi-receipt-cutoff text-success me-2"></i>Confirmar compra';
    }

    const modalSubtitle = document.getElementById('checkoutModalSubtitle');
    if (modalSubtitle) {
        modalSubtitle.textContent = RAFFLE_IS_FREE
            ? 'Completa tus datos para registrar tu número'
            : 'Completa tus datos para reservar';
    }

    const confirmBtn = document.querySelector('#checkoutModal button.btn-success');
    if (confirmBtn && !confirmBtn.disabled) {
        confirmBtn.innerHTML = RAFFLE_IS_FREE
            ? '<i class="bi bi-check-circle me-1"></i>Registrar'
            : '<i class="bi bi-check-circle me-1"></i>Confirmar';
    }

    const numbersHint = document.getElementById('numbersSectionHint');
    if (numbersHint) {
        numbersHint.textContent = RAFFLE_IS_FREE
            ? 'Elige 1 número disponible (1 por persona)'
            : 'Toca un número disponible para seleccionarlo';
    }
}

function renderPremios(texto) {
    const wrapper = document.getElementById('premiosWrapper');
    if (!wrapper || !texto) return;
    wrapper.innerHTML = '';
    const premios = texto.split(',');
    premios.forEach((raw, index) => {
        const txt = raw.trim();
        if (!txt) return;
        const valorMatch = txt.match(/\d{4,}/);
        const valor = valorMatch ? Number(valorMatch[0]) : null;
        const titulo = valorMatch ? txt.substring(0, valorMatch.index).trim() : txt;
        const detalle = valorMatch ? txt.substring(valorMatch.index + valorMatch[0].length).trim() : '';
        const esPrincipal = index === 0;

        wrapper.innerHTML += `
            <div class="${esPrincipal ? 'col-12' : 'col-6 col-md-4'}">
                <div class="card h-100 premio-card ${esPrincipal ? 'premio-card--main shadow-sm' : 'border-0 shadow-sm'}">
                    <div class="card-body text-center py-4">
                        <div class="premio-card__icon mb-3">
                            <i class="bi ${esPrincipal ? 'bi-trophy-fill' : 'bi-gift'}"></i>
                        </div>
                        <h6 class="fw-bold mb-2">${escHtml(titulo)}</h6>
                        ${valor ? `<div class="${esPrincipal ? 'fs-3' : 'fs-5'} fw-bold text-success mb-1">$${valor.toLocaleString('es-CO')}</div>` : ''}
                        ${detalle ? `<small class="text-muted">${escHtml(detalle)}</small>` : ''}
                    </div>
                </div>
            </div>`;
    });
}

function mostrarAlertaUI({ type = 'error', title = 'Atención', message = '', blocking = true }) {
    const modalEl = document.getElementById('uiAlertModal');
    if (!modalEl) return;
    const titleEl = modalEl.querySelector('.modal-title');
    const bodyEl = modalEl.querySelector('.modal-body');
    const iconEl = modalEl.querySelector('.alert-icon');
    const config = {
        error:   { icon: 'bi-x-circle-fill', class: 'text-danger' },
        warning: { icon: 'bi-exclamation-triangle-fill', class: 'text-warning' },
        info:    { icon: 'bi-info-circle-fill', class: 'text-info' },
        success: { icon: 'bi-check-circle-fill', class: 'text-success' },
    };
    const c = config[type] || config.error;
    if (iconEl) {
        iconEl.className = `alert-icon fs-2 d-inline-flex align-items-center justify-content-center rounded-circle landing-alert-icon ${c.class}`;
        iconEl.innerHTML = `<i class="bi ${c.icon}"></i>`;
    }
    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.innerHTML = message;
    new bootstrap.Modal(modalEl, { backdrop: blocking ? 'static' : true, keyboard: !blocking }).show();
}

function abrirCheckoutModal() {
    if (!RAFFLE_ACTIVE) {
        mostrarRifaDetenida();
        return;
    }
    if (selectedTickets.size === 0) {
        mostrarAlertaUI({ type: 'warning', title: 'Sin selección', message: 'Elige al menos un número.' });
        return;
    }
    const numeros = [...selectedTickets.values()].join(', ');
    const total = selectedTickets.size * PRECIO_BOLETA;
    const modalNumbersEl = document.getElementById('modalNumbers');
    const modalTotalEl = document.getElementById('modalTotal');
    if (modalNumbersEl) modalNumbersEl.innerText = numeros;
    if (modalTotalEl) modalTotalEl.innerText = total.toLocaleString('es-CO');

    resetCheckoutForm();

    const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    modal.show();

    document.getElementById('checkoutModal')?.addEventListener('shown.bs.modal', () => {
        document.getElementById('clientPhone')?.focus();
    }, { once: true });
}

function pickRandom() {
    if (!RAFFLE_ACTIVE) {
        mostrarRifaDetenida();
        return;
    }
    const disponibles = cache.filter(t => Number(t.status_ticket) === 0 && !selectedTickets.has(Number(t.id_ticket)));
    if (disponibles.length === 0) return;
    const random = disponibles[Math.floor(Math.random() * disponibles.length)];
    toggleSeleccion(Number(random.id_ticket), String(random.number_ticket), null);
    renderGrid();
}

function mostrarMantenimiento() {
    const modalEl = document.getElementById('siteMaintenanceModal');
    if (!modalEl) return;
    new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }).show();
}

function mostrarRifaDetenida() {
    const banner = document.getElementById('rafflePausedBanner');
    if (banner) banner.classList.remove('d-none');

    selectedTickets.clear();
    updateCheckoutUI();
    renderGrid();

    const mobileCheckout = document.getElementById('mobileCheckout');
    if (mobileCheckout) mobileCheckout.classList.add('d-none');

    document.querySelectorAll('button[onclick="pickRandom()"]').forEach(el => {
        el.classList.add('d-none');
    });

    document.querySelectorAll('aside .btn-success, #btnCheckoutMobile').forEach(el => {
        el.setAttribute('disabled', 'disabled');
        el.classList.add('disabled');
    });

    const paginacion = document.getElementById('paginacionContainer');
    if (paginacion) paginacion.classList.add('d-none');

    const numbersCard = document.querySelector('#numbers .card');
    if (numbersCard) numbersCard.classList.add('raffle-paused');
}

function ocultarRifaDetenida() {
    const banner = document.getElementById('rafflePausedBanner');
    if (banner) banner.classList.add('d-none');

    const mobileCheckout = document.getElementById('mobileCheckout');
    if (mobileCheckout) mobileCheckout.classList.remove('d-none');

    document.querySelectorAll('button[onclick="pickRandom()"]').forEach(el => {
        el.classList.remove('d-none');
    });

    document.querySelectorAll('aside .btn-success, #btnCheckoutMobile').forEach(el => {
        el.removeAttribute('disabled');
        el.classList.remove('disabled');
    });

    const paginacion = document.getElementById('paginacionContainer');
    if (paginacion) paginacion.classList.remove('d-none');

    const numbersCard = document.querySelector('#numbers .card');
    if (numbersCard) numbersCard.classList.remove('raffle-paused');
}