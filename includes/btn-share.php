<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
(function () {
    let voucherShareCache = null;
    let preparingShare = false;
    let prepareTimer = null;

    function normalizeWhatsAppPhone(raw) {
        const digits = String(raw || '').replace(/\D/g, '');
        if (!digits) return '';
        if (digits.length === 10 && digits.startsWith('3')) return '57' + digits;
        if (digits.startsWith('57') && digits.length >= 12) return digits;
        return digits.length >= 11 ? digits : '';
    }

    function buildWhatsAppUrl(phone, text) {
        return 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(phone)
            + '&text=' + encodeURIComponent(text);
    }

    function abrirWhatsAppCliente(phone, text) {
        window.location.href = buildWhatsAppUrl(phone, text);
    }

    function buildMensajeComprobante(nombre, url) {
        const saludo = nombre ? 'Hola, ' + nombre + ' 👋' : 'Hola 👋';
        return saludo
            + '\n\n¡Gracias por tu participación! 🙌'
            + '\nAquí está tu comprobante de venta 🎟️✨'
            + '\n\n' + url
            + '\n\n¡Mucha suerte! 🍀';
    }

    function getShareButton() {
        return document.querySelector('#voucherCapture a[onclick*="shareVoucher"]');
    }

    function resetShareButton(btn) {
        if (!btn) return;
        btn.style.opacity = '';
        btn.style.pointerEvents = 'auto';
    }

    function invalidateShareCache() {
        voucherShareCache = null;
    }

    async function buildVoucherShareFile() {
        const target = document.getElementById('voucherCapture');
        if (!target || typeof html2canvas === 'undefined') return null;

        const codigo = target.querySelector('[data-codigo]')?.getAttribute('data-codigo') || 'comprobante';
        if (voucherShareCache && voucherShareCache.codigo === codigo) {
            return voucherShareCache;
        }

        const canvas = await html2canvas(target, {
            backgroundColor: '#f4f6f8',
            scale: 2,
            useCORS: true,
            logging: false,
        });

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        if (!blob) return null;

        const fileName = 'ticket-' + codigo + '.png';
        const file = new File([blob], fileName, { type: 'image/png' });

        voucherShareCache = { codigo, file, blob, fileName };
        return voucherShareCache;
    }

    async function prepareVoucherShare() {
        if (preparingShare || !document.getElementById('voucherCapture')) return;

        preparingShare = true;
        try {
            await buildVoucherShareFile();
        } catch (err) {
            console.error('Error al preparar comprobante:', err);
            invalidateShareCache();
        } finally {
            preparingShare = false;
        }
    }

    function schedulePrepareVoucherShare() {
        clearTimeout(prepareTimer);
        prepareTimer = setTimeout(prepareVoucherShare, 350);
    }

    async function subirComprobante(blob, fileName, idSale) {
        const fd = new FormData();
        fd.append('action', 'subir_comprobante_whatsapp');
        fd.append('comprobante', blob, fileName);
        if (idSale) fd.append('id_sale', idSale);

        if (typeof API !== 'undefined' && API.post) {
            return API.post('ventas', fd);
        }

        fd.append('module', 'ventas');
        const res = await fetch(window.API_URL || 'front/ajax/api.php', {
            method: 'POST',
            body: fd,
            cache: 'no-store',
        });
        return res.json();
    }

    function aviso(mensaje, tipo) {
        if (typeof alertify === 'undefined') return;
        if (tipo === 'error') alertify.error(mensaje, 5);
        else alertify.success(mensaje, 4);
    }

    window.shareVoucher = async function shareVoucher(btn) {
        const target = document.getElementById('voucherCapture');
        if (!target || typeof html2canvas === 'undefined') return;

        const phone = normalizeWhatsAppPhone(target.dataset.phoneCustomer || '');
        const customerName = (target.dataset.customerName || '').trim();
        const idSale = (target.dataset.idSale || '').trim();

        if (!phone) {
            aviso('Este comprobante no tiene celular del cliente.', 'error');
            return;
        }

        if (btn) {
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
        }

        try {
            const cache = await buildVoucherShareFile();
            if (!cache) {
                aviso('No se pudo generar la imagen del comprobante.', 'error');
                return;
            }

            const uploaded = await subirComprobante(cache.blob, cache.fileName, idSale);
            if (!uploaded?.success || !uploaded.url) {
                aviso(uploaded?.message || 'No se pudo subir el comprobante.', 'error');
                return;
            }

            const mensaje = buildMensajeComprobante(customerName, uploaded.url);
            abrirWhatsAppCliente(phone, mensaje);
        } catch (err) {
            console.error('Error al compartir comprobante:', err);
            aviso('Error al compartir. Intenta de nuevo.', 'error');
        } finally {
            resetShareButton(btn);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const observer = new MutationObserver(function () {
            if (!document.getElementById('voucherCapture')) {
                invalidateShareCache();
                return;
            }
            schedulePrepareVoucherShare();
        });

        observer.observe(document.body, { childList: true, subtree: true });

        if (document.getElementById('voucherCapture')) {
            schedulePrepareVoucherShare();
        }
    });
})();
</script>
