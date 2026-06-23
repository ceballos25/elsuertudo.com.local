<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
(function () {
    let voucherShareCache = null;
    let preparingShare = false;
    let prepareTimer = null;

    function esIOS() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent)
            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function getShareButton() {
        return document.querySelector('#voucherCapture a[onclick*="shareVoucher"]');
    }

    function resetShareButton(btn) {
        if (!btn) return;
        btn.dataset.sharePending = '0';
        btn.style.opacity = '';
        btn.style.boxShadow = '';
        btn.style.pointerEvents = 'auto';
    }

    function markSharePending(btn) {
        if (!btn) return;
        btn.dataset.sharePending = '1';
        btn.style.opacity = '1';
        btn.style.boxShadow = '0 0 0 3px rgba(4, 217, 18, 0.45)';
        btn.style.pointerEvents = 'auto';
        if (typeof alertify !== 'undefined') {
            alertify.message('Listo. Toca Compartir otra vez.', 3);
        }
    }

    function invalidateShareCache() {
        voucherShareCache = null;
        resetShareButton(getShareButton());
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
            voucherShareCache = null;
        } finally {
            preparingShare = false;
        }
    }

    function schedulePrepareVoucherShare() {
        clearTimeout(prepareTimer);
        prepareTimer = setTimeout(() => {
            prepareVoucherShare();
        }, 350);
    }

    async function openNativeShare(file) {
        if (!navigator.canShare || !navigator.canShare({ files: [file] })) {
            return false;
        }
        await navigator.share({ files: [file], title: 'Comprobante de venta' });
        return true;
    }

    function downloadShareBlob(cache) {
        const url = URL.createObjectURL(cache.blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = cache.fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    window.shareVoucher = async function shareVoucher(btn) {
        const target = document.getElementById('voucherCapture');
        if (!target || typeof html2canvas === 'undefined') return;

        const codigo = target.querySelector('[data-codigo]')?.getAttribute('data-codigo') || 'comprobante';

        if (btn) {
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
        }

        try {
            // iOS: segundo toque con gesto válido (Safari pierde el gesto tras html2canvas)
            if (btn?.dataset?.sharePending === '1' && voucherShareCache?.codigo === codigo) {
                resetShareButton(btn);
                await openNativeShare(voucherShareCache.file);
                return;
            }

            // Imagen ya precargada → compartir en el mismo toque
            if (voucherShareCache?.codigo === codigo) {
                try {
                    const shared = await openNativeShare(voucherShareCache.file);
                    if (shared) return;
                    downloadShareBlob(voucherShareCache);
                    return;
                } catch (err) {
                    if (err?.name === 'AbortError') return;
                    if (err?.name === 'NotAllowedError' && esIOS()) {
                        markSharePending(btn);
                        return;
                    }
                    throw err;
                }
            }

            const cache = await buildVoucherShareFile();
            if (!cache) return;

            if (esIOS()) {
                markSharePending(btn);
                return;
            }

            try {
                const shared = await openNativeShare(cache.file);
                if (!shared) downloadShareBlob(cache);
            } catch (err) {
                if (err?.name === 'AbortError') return;
                if (err?.name === 'NotAllowedError') {
                    markSharePending(btn);
                    return;
                }
                throw err;
            }
        } catch (err) {
            if (err?.name !== 'AbortError') {
                console.error('Error al compartir comprobante:', err);
            }
        } finally {
            if (btn && btn.dataset.sharePending !== '1') {
                resetShareButton(btn);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const observer = new MutationObserver(function () {
            const target = document.getElementById('voucherCapture');
            if (!target) {
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
