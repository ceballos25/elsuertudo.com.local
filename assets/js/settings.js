let settingsCache = [];
let modalSetting = null;

document.addEventListener('DOMContentLoaded', () => {
    modalSetting = new bootstrap.Modal(document.getElementById('modalSetting'));
    cargarSettings();
});

/* =========================
   HELPERS UX
========================= */

function formatWhatsapp(num) {
    if (!num || num.length < 10) return num;
    return `+${num.slice(0,2)} ${num.slice(2,5)} ${num.slice(5,8)} ${num.slice(8)}`;
}

function shortenUrl(url) {
    try {
        const u = new URL(url);
        return u.hostname.replace('www.', '');
    } catch {
        return url;
    }
}

/* =========================
   CARGAR SETTINGS
========================= */

async function cargarSettings() {
    try {
        const data = await API.post('settings', { action: 'obtener' });

        if (data.success) {
            settingsCache = data.data || [];
            renderSettings();
        }
    } catch (e) {
        console.error(e);
    }
}

/* =========================
   RENDER DESKTOP + MOBILE
========================= */

function renderSettings() {
    const tbody = document.getElementById('bodySettings');
    const cards = document.getElementById('settingsCards');

    tbody.innerHTML = '';
    cards.innerHTML = '';

    settingsCache.forEach(s => {

        let valueHtml = '';

        // IMAGE
        if (s.type_setting === 'image') {
            valueHtml = `
                <div class="d-flex align-items-center gap-2">
                    <img src="/${s.value_setting}" class="rounded"
                         style="width:50px;height:40px;object-fit:cover">
                    <span class="badge bg-light text-dark">
                        ${s.extra_setting?.toUpperCase() || ''}
                    </span>
                </div>`;
        }

        // BOOLEAN
        else if (s.type_setting === 'boolean') {
            const active = s.value_setting == 1;
            valueHtml = `
                <span class="badge ${active ? 'bg-success' : 'bg-danger'}">
                    ${active ? 'Activo' : 'Inactivo'}
                </span>`;
        }

        // URL
        else if (s.type_setting === 'url') {
            valueHtml = `
                <a href="${s.value_setting}" target="_blank"
                   title="${s.value_setting}">
                    ${shortenUrl(s.value_setting)}
                </a>`;
        }

        // TEXT / WHATSAPP
        else {
            valueHtml = s.key_setting.includes('whatsapp')
                ? `<a href="https://wa.me/${s.value_setting}" target="_blank">
                    <i class="ti ti-brand-whatsapp text-success"></i>
                    ${formatWhatsapp(s.value_setting)}
                   </a>`
                : s.value_setting;
        }

        // DESKTOP
        tbody.innerHTML += `
            <tr>
                <td class="col-titulo">
                    <strong>${cellTruncate(s.description_setting)}</strong>
                    <div class="text-muted small">${cellTruncate(s.key_setting)}</div>
                </td>
                <td class="col-text">${valueHtml}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary"
                            title="Editar configuración"
                            onclick="editarSetting(${s.id_setting})">
                        <i class="ti ti-edit"></i>
                    </button>
                </td>
            </tr>
        `;

        // MOBILE
        cards.innerHTML += `
            <div class="card shadow-sm mb-2">
                <div class="card-body">
                    <div class="fw-bold mb-1">${s.description_setting}</div>
                    <div class="mb-2">${valueHtml}</div>
                    <button class="btn btn-outline-primary btn-sm w-100"
                            onclick="editarSetting(${s.id_setting})">
                        <i class="ti ti-edit"></i> Editar
                    </button>
                </div>
            </div>
        `;
    });
}

/* =========================
   EDITAR SETTING
========================= */

function editarSetting(id) {
    const s = settingsCache.find(x => x.id_setting == id);
    if (!s) return;

    const container = document.getElementById('inputContainer');
    const modalTitle = document.querySelector('#modalSetting .modal-title');
    const previewBox = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    // RESET
    container.innerHTML = '';
    previewBox.classList.add('d-none');
    previewImg.src = '';

    document.getElementById('id_setting').value = s.id_setting;
    modalTitle.innerText = s.description_setting;

    // TEXT / URL
    if (['text', 'url'].includes(s.type_setting)) {
        container.innerHTML = `
            <input class="form-control" id="value_setting"
                   value="${s.value_setting}">
        `;
    }

    // BOOLEAN
    else if (s.type_setting === 'boolean') {
        container.innerHTML = `
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       id="value_setting"
                       ${s.value_setting == 1 ? 'checked' : ''}>
            </div>
        `;
    }

    // IMAGE
    else if (s.type_setting === 'image') {
        container.innerHTML = `
            <input type="file" class="form-control"
                   id="value_setting" accept="image/*">
        `;
        previewImg.src = '/' + s.value_setting;
        previewBox.classList.remove('d-none');
    }

    modalSetting.show();
}

/* =========================
   GUARDAR
========================= */

async function guardarSetting() {
    const fd = new FormData();
    fd.append('action', 'actualizar');
    fd.append('id_setting', document.getElementById('id_setting').value);

    const input = document.getElementById('value_setting');

    if (input.type === 'file') {
        if (input.files[0]) fd.append('file', input.files[0]);
    }
    else if (input.type === 'checkbox') {
        fd.append('value_setting', input.checked ? 1 : 0);
    }
    else {
        fd.append('value_setting', input.value);
    }

    try {
        const data = await API.post('settings', fd);

        if (data.success) {
            modalSetting.hide();
            cargarSettings();
            alertify.success(data.message || 'Actualizado');
        } else {
            alertify.error(data.message || 'Error');
        }
    } catch {
        alertify.error('Error en la solicitud');
    }
}

/* =========================
   PREVIEW INSTANTÁNEO
========================= */

document.addEventListener('change', e => {
    if (e.target.id === 'value_setting' && e.target.type === 'file') {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('previewImg').src = ev.target.result;
            document.getElementById('imagePreview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});
