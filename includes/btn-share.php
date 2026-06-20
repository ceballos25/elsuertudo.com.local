<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
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
        const fileName = `ticket-${codigo}.png`;
        const blob = await new Promise(res => canvas.toBlob(res, 'image/png'));
        if (!blob) return;

        const file = new File([blob], fileName, { type: 'image/png' });

        if (navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], title: 'Comprobante de venta' });
        } else {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            link.click();
            URL.revokeObjectURL(url);
        }
    } catch (err) {
        console.error('Error al compartir comprobante:', err);
    } finally {
        if (btn) {
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = originalOpacity || '1';
        }
    }
}
</script>
