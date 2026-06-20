<script>
let modalCallback = null;
let modalInstance = null;

function getModalGlobalParts() {
    return {
        modalEl: document.getElementById('modalGlobal'),
        btnConfirm: document.getElementById('modalGlobalConfirm'),
        btnCancel: document.getElementById('modalGlobalCancel'),
        titleEl: document.getElementById('modalGlobalTitle'),
        bodyEl: document.getElementById('modalGlobalBody'),
    };
}

function ensureModalInstance() {
    const { modalEl } = getModalGlobalParts();
    if (!modalEl) return null;
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modalEl);
    }
    return modalInstance;
}

function confirmarAccion({
    titulo,
    mensaje,
    html,
    textoConfirmar = 'Confirmar',
    tipoConfirmar = 'danger',
    onConfirm,
}) {
    const { modalEl, btnConfirm, btnCancel, titleEl, bodyEl } = getModalGlobalParts();

    if (!modalEl || !btnConfirm || !titleEl || !bodyEl) {
        console.error('modalGlobal no encontrado');
        return;
    }

    if (btnCancel) btnCancel.classList.remove('d-none');

    titleEl.innerText = titulo || 'Confirmar';
    bodyEl.innerHTML = html
        || `<p class="mb-0">${escapeHtml(mensaje || '¿Deseas continuar?')}</p>`;

    btnConfirm.className = `btn btn-${tipoConfirmar} btn-sm`;
    btnConfirm.innerText = textoConfirmar;
    btnConfirm.disabled = false;

    modalCallback = onConfirm;

    const instance = ensureModalInstance();
    if (!instance) return;

    instance.show();

    btnConfirm.onclick = async () => {
        if (typeof modalCallback !== 'function') {
            instance.hide();
            return;
        }

        const labelOriginal = btnConfirm.innerText;
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando...';

        try {
            const result = await modalCallback();
            modalCallback = null;

            const inform = result?.inform;
            if (inform) {
                modalEl.addEventListener('hidden.bs.modal', () => informarAccion(inform), { once: true });
            }

            instance.hide();
        } catch (e) {
            console.error(e);
        } finally {
            btnConfirm.disabled = false;
            btnConfirm.innerText = labelOriginal;
        }
    };
}

function informarAccion({
    titulo,
    mensaje,
    html,
    textoCerrar = 'Entendido',
    tipoCerrar = 'success',
    onClose,
}) {
    const { modalEl, btnConfirm, btnCancel, titleEl, bodyEl } = getModalGlobalParts();

    if (!modalEl || !btnConfirm || !titleEl || !bodyEl) {
        console.error('modalGlobal no encontrado');
        return;
    }

    if (btnCancel) btnCancel.classList.add('d-none');

    titleEl.innerText = titulo || 'Listo';
    bodyEl.innerHTML = html
        || `<p class="mb-0">${escapeHtml(mensaje || '')}</p>`;

    btnConfirm.className = `btn btn-${tipoCerrar} btn-sm`;
    btnConfirm.innerText = textoCerrar;
    btnConfirm.disabled = false;

    modalCallback = null;

    const instance = ensureModalInstance();
    if (!instance) return;

    btnConfirm.onclick = () => {
        instance.hide();
        if (typeof onClose === 'function') onClose();
    };

    instance.show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.confirmarAccion = confirmarAccion;
window.informarAccion = informarAccion;
</script>

<div class="modal fade" id="modalGlobal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">

      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold" id="modalGlobalTitle">Confirmar</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body small" id="modalGlobalBody">
        ¿Seguro?
      </div>

      <div class="modal-footer py-2">
        <button class="btn btn-light btn-sm" id="modalGlobalCancel" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button class="btn btn-danger btn-sm" id="modalGlobalConfirm">
          Confirmar
        </button>
      </div>

    </div>
  </div>
</div>
