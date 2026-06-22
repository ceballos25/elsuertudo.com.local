<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
/**
 * Compartir comprobante por WhatsApp
 *
 * Documentación oficial WhatsApp:
 * - wa.me / whatsapp://send → solo texto prellenado, NO adjunta archivos
 *   https://faq.whatsapp.com/425247423114725
 * - Imágenes en móvil → Share Extension (iOS) / Web Share API con files (Android)
 *   WhatsApp acepta public.image vía menú compartir del sistema
 */
function normalizeWhatsAppPhone(raw) {
    const digits = String(raw || '').replace(/\D/g, '');
    if (!digits) return '';
    if (digits.length === 10 && digits.startsWith('3')) return '57' + digits;
    if (digits.startsWith('57') && digits.length >= 12) return digits;
    return digits.length >= 11 ? digits : '';
}

function buildWhatsAppUrl(phone, text) {
    const base = 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(phone);
    return text ? base + '&text=' + encodeURIComponent(text) : base;
}

function esAndroid() {
    return /Android/i.test(navigator.userAgent);
}

function esIOS() {
    return /iPhone|iPad|iPod/i.test(navigator.userAgent);
}

function esMovil() {
    return esAndroid() || esIOS();
}

function puedeCompartirArchivo(file) {
    return typeof navigator.canShare === 'function' && navigator.canShare({ files: [file] });
}

function descargarImagenComprobante(blob, fileName) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function avisoCompartir(mensaje, tipo) {
    if (typeof alertify === 'undefined') return;
    if (tipo === 'error') alertify.error(mensaje, 6);
    else if (tipo === 'success') alertify.success(mensaje, 5);
    else alertify.message(mensaje, 6);
}

function pedirCompartirConGesto(compartirFn) {
    if (typeof alertify !== 'undefined' && alertify.confirm) {
        alertify.confirm(
            'Enviar comprobante',
            '<p class="mb-2">Toca <strong>Enviar por WhatsApp</strong>.</p>' +
            '<p class="mb-0 small text-muted">Elige WhatsApp en el menú. La imagen irá adjunta; solo pulsa Enviar al cliente.</p>',
            compartirFn,
            function () {}
        ).set('labels', { ok: 'Enviar por WhatsApp', cancel: 'Cancelar' });
        return;
    }
    compartirFn();
}

async function compartirImagenWhatsAppOficial(file, mensaje) {
    await navigator.share({
        files: [file],
        text: mensaje,
        title: 'Comprobante de venta',
    });
}

async function intentarCompartirMovil(file, mensaje) {
    if (!esMovil() || !puedeCompartirArchivo(file)) {
        return false;
    }

    const ejecutar = () => compartirImagenWhatsAppOficial(file, mensaje);

    try {
        await ejecutar();
        return true;
    } catch (err) {
        if (err?.name === 'AbortError') {
            throw err;
        }

        // iOS pierde el gesto del usuario tras html2canvas → segundo tap oficial
        if (err?.name === 'NotAllowedError') {
            return new Promise((resolve, reject) => {
                pedirCompartirConGesto(async () => {
                    try {
                        await ejecutar();
                        resolve(true);
                    } catch (e2) {
                        if (e2?.name === 'AbortError') reject(e2);
                        else resolve(false);
                    }
                });
            });
        }
    }

    return false;
}

async function shareVoucher(btn) {
    const target = document.getElementById('voucherCapture');
    if (!target || typeof html2canvas === 'undefined') return;

    const originalOpacity = btn?.style.opacity;
    if (btn) {
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.5';
    }

    try {
        const canvas = await html2canvas(target, {
            backgroundColor: '#f4f6f8',
            scale: 2,
            useCORS: true,
            logging: false,
        });

        const codigo = target.querySelector('[data-codigo]')?.getAttribute('data-codigo') || 'comprobante';
        const fileName = 'ticket-' + codigo + '.png';
        const blob = await new Promise(res => canvas.toBlob(res, 'image/png'));
        if (!blob) return;

        const file = new File([blob], fileName, { type: 'image/png' });
        const customerName = (target.dataset.customerName || '').trim();
        const phone = normalizeWhatsAppPhone(target.dataset.phoneCustomer || '');
        const mensaje = customerName
            ? '¡Hola ' + customerName + '! Te envío tu comprobante de compra 🎟️'
            : '¡Hola! Te envío tu comprobante de compra 🎟️';

        // Móvil: método oficial WhatsApp (Share Extension / Web Share con imagen)
        if (esMovil()) {
            if (!puedeCompartirArchivo(file)) {
                avisoCompartir(
                    'Tu navegador no permite adjuntar imágenes. Usa Chrome o Safari actualizado.',
                    'error'
                );
                descargarImagenComprobante(blob, fileName);
                if (phone) {
                    window.open(buildWhatsAppUrl(phone, mensaje), '_blank');
                }
                return;
            }

            const compartido = await intentarCompartirMovil(file, mensaje);
            if (compartido) {
                return;
            }

            avisoCompartir('No se pudo abrir el menú de compartir. Descargando imagen…', 'error');
            descargarImagenComprobante(blob, fileName);
            return;
        }

        // Escritorio: wa.me no admite archivos (documentación oficial)
        if (phone) {
            descargarImagenComprobante(blob, fileName);
            window.open(buildWhatsAppUrl(phone, mensaje), '_blank');
            avisoCompartir(
                'En computador WhatsApp no adjunta archivos por enlace. Descarga la imagen y adjúntala con 📎 en el chat abierto.',
                'error'
            );
            return;
        }

        if (puedeCompartirArchivo(file)) {
            await navigator.share({ files: [file], title: 'Comprobante de venta', text: mensaje });
        } else {
            descargarImagenComprobante(blob, fileName);
        }
    } catch (err) {
        if (err?.name !== 'AbortError') {
            console.error('Error al compartir comprobante:', err);
            avisoCompartir('Error al compartir. Intenta de nuevo.', 'error');
        }
    } finally {
        if (btn) {
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = originalOpacity || '1';
        }
    }
}
</script>
