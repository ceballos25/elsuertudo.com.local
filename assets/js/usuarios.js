/**
 * Gestión de usuarios (solo administrador)
 */
let usuariosCache = [];
let modalUsuario = null;
let modalPasswordAdmin = null;

document.addEventListener('DOMContentLoaded', () => {
    const elUsuario = document.getElementById('modalUsuario');
    const elPassword = document.getElementById('modalPasswordAdmin');

    if (elUsuario) modalUsuario = new bootstrap.Modal(elUsuario);
    if (elPassword) modalPasswordAdmin = new bootstrap.Modal(elPassword);

    cargarUsuarios();
});

function rolLabel(rol) {
    return rol === 'administrador'
        ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Administrador</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Vendedor</span>';
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

function mostrarFeedbackModal(id, tipo, mensaje) {
    const box = document.getElementById(id);
    if (!box) return;

    const clases = {
        success: 'alert-success',
        error: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info',
    };

    box.className = `alert ${clases[tipo] || 'alert-info'} mb-3`;
    box.textContent = mensaje;
    box.classList.remove('d-none');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function ocultarFeedbackModal(id) {
    const box = document.getElementById(id);
    if (!box) return;
    box.className = 'alert d-none mb-3';
    box.textContent = '';
}

function setBotonCargando(btn, cargando, textoNormal) {
    if (!btn) return;
    btn.disabled = cargando;
    btn.innerHTML = cargando
        ? '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...'
        : textoNormal;
}

async function cargarUsuarios() {
    const tbody = document.getElementById('bodyUsuarios');
    if (!tbody) return;

    try {
        const res = await API.post('usuarios', { action: 'obtener' });

        if (!res.success) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger">${escHtml(res.message || 'Error al cargar')}</td></tr>`;
            alertify.error(res.message || 'Error al cargar usuarios');
            return;
        }

        usuariosCache = res.data || [];
        renderTablaUsuarios();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Error de conexión</td></tr>';
        alertify.error('Error de conexión');
    }
}

function renderTablaUsuarios() {
    const tbody = document.getElementById('bodyUsuarios');
    const yo = window.APP_USER?.id;

    if (!usuariosCache.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No hay usuarios registrados</td></tr>';
        return;
    }

    tbody.innerHTML = usuariosCache.map(u => {
        const activo = Number(u.status_admin) === 1;
        const esYo = Number(u.id_admin) === Number(yo);

        const acciones = activo ? `
            <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(${u.id_admin})" title="Editar">
                <i class="ti ti-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="abrirModalPasswordAdmin(${u.id_admin})" title="Cambiar contraseña">
                <i class="ti ti-key"></i>
            </button>
            ${esYo ? '' : `
            <button class="btn btn-sm btn-outline-danger" onclick="desactivarUsuario(${u.id_admin})" title="Desactivar">
                <i class="ti ti-user-off"></i>
            </button>`}
        ` : '<span class="text-muted small">Inactivo</span>';

        return `
            <tr class="${activo ? '' : 'opacity-50'}">
                <td class="ps-4 col-cliente mobile-card-head" data-label="">
                    <span class="fw-semibold">${cellTruncate(u.email_admin)}</span>
                    ${esYo ? '<span class="badge bg-light text-dark border ms-1">Tú</span>' : ''}
                </td>
                <td data-label="Rol">${rolLabel(u.rol_admin)}</td>
                <td data-label="Estado">${activo ? '<span class="text-success">Activo</span>' : '<span class="text-muted">Inactivo</span>'}</td>
                <td data-label="Creado">${escHtml(u.date_created_admin || '-')}</td>
                <td class="text-end pe-4 mobile-card-actions" data-label="">
                    <div class="btn-group btn-group-sm">${acciones}</div>
                </td>
            </tr>`;
    }).join('');
}

function abrirModalUsuario() {
    document.getElementById('usuarioId').value = '';
    document.getElementById('usuarioEmail').value = '';
    document.getElementById('usuarioPassword').value = '';
    document.getElementById('usuarioRol').value = 'vendedor';
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo usuario';
    document.getElementById('grupoPassword').classList.remove('d-none');
    ocultarFeedbackModal('usuarioFeedback');
    setBotonCargando(document.getElementById('btnGuardarUsuario'), false, 'Guardar');
    modalUsuario?.show();
}

function editarUsuario(id) {
    const u = usuariosCache.find(x => Number(x.id_admin) === Number(id));
    if (!u) return;

    document.getElementById('usuarioId').value = u.id_admin;
    document.getElementById('usuarioEmail').value = u.email_admin;
    document.getElementById('usuarioPassword').value = '';
    document.getElementById('usuarioRol').value = u.rol_admin === 'administrador' ? 'administrador' : 'vendedor';
    document.getElementById('modalUsuarioTitulo').textContent = 'Editar usuario';
    document.getElementById('grupoPassword').classList.add('d-none');
    ocultarFeedbackModal('usuarioFeedback');
    setBotonCargando(document.getElementById('btnGuardarUsuario'), false, 'Guardar');
    modalUsuario?.show();
}

async function guardarUsuario() {
    const id = document.getElementById('usuarioId').value;
    const email = document.getElementById('usuarioEmail').value.trim();
    const password = document.getElementById('usuarioPassword').value;
    const rol = document.getElementById('usuarioRol').value;
    const btn = document.getElementById('btnGuardarUsuario');

    ocultarFeedbackModal('usuarioFeedback');

    if (!email) {
        mostrarFeedbackModal('usuarioFeedback', 'warning', 'Ingresa el email o nombre de usuario.');
        alertify.error('Ingresa el email o usuario');
        return;
    }

    const payload = {
        email_admin: email,
        rol_admin: rol,
    };

    if (id) {
        payload.action = 'actualizar';
        payload.id_admin = id;
    } else {
        if (password.length < 6) {
            mostrarFeedbackModal('usuarioFeedback', 'warning', 'La contraseña debe tener al menos 6 caracteres.');
            alertify.error('La contraseña debe tener al menos 6 caracteres');
            return;
        }
        payload.action = 'crear';
        payload.password_admin = password;
    }

    setBotonCargando(btn, true, 'Guardar');

    try {
        const res = await API.post('usuarios', payload);

        if (res.success) {
            mostrarFeedbackModal('usuarioFeedback', 'success', res.message || 'Usuario guardado correctamente');
            alertify.success(res.message || 'Usuario guardado', 6);
            await cargarUsuarios();
            setTimeout(() => modalUsuario?.hide(), 600);
        } else {
            const msg = res.message || 'No se pudo guardar el usuario';
            mostrarFeedbackModal('usuarioFeedback', 'error', msg);
            alertify.error(msg, 8);
        }
    } catch (e) {
        console.error(e);
        const msg = 'Error de conexión. Intenta de nuevo.';
        mostrarFeedbackModal('usuarioFeedback', 'error', msg);
        alertify.error(msg, 8);
    } finally {
        setBotonCargando(btn, false, 'Guardar');
    }
}

function abrirModalPasswordAdmin(id) {
    const u = usuariosCache.find(x => Number(x.id_admin) === Number(id));
    if (!u) return;

    document.getElementById('passwordAdminId').value = u.id_admin;
    document.getElementById('passwordAdminEmail').textContent = `Usuario: ${u.email_admin}`;
    document.getElementById('passwordAdminNueva').value = '';
    ocultarFeedbackModal('passwordAdminFeedback');
    setBotonCargando(document.getElementById('btnGuardarPasswordAdmin'), false, 'Actualizar');
    modalPasswordAdmin?.show();
}

async function guardarPasswordAdmin() {
    const id = document.getElementById('passwordAdminId').value;
    const password = document.getElementById('passwordAdminNueva').value;
    const btn = document.getElementById('btnGuardarPasswordAdmin');

    ocultarFeedbackModal('passwordAdminFeedback');

    if (password.length < 6) {
        mostrarFeedbackModal('passwordAdminFeedback', 'warning', 'La contraseña debe tener al menos 6 caracteres.');
        alertify.error('La contraseña debe tener al menos 6 caracteres');
        return;
    }

    setBotonCargando(btn, true, 'Actualizar');

    try {
        const res = await API.post('usuarios', {
            action: 'cambiar_password',
            id_admin: id,
            password_admin: password,
        });

        if (res.success) {
            mostrarFeedbackModal('passwordAdminFeedback', 'success', res.message || 'Contraseña actualizada');
            alertify.success(res.message, 6);
            setTimeout(() => modalPasswordAdmin?.hide(), 600);
        } else {
            const msg = res.message || 'No se pudo cambiar la contraseña';
            mostrarFeedbackModal('passwordAdminFeedback', 'error', msg);
            alertify.error(msg, 8);
        }
    } catch (e) {
        console.error(e);
        const msg = 'Error de conexión. Intenta de nuevo.';
        mostrarFeedbackModal('passwordAdminFeedback', 'error', msg);
        alertify.error(msg, 8);
    } finally {
        setBotonCargando(btn, false, 'Actualizar');
    }
}

function desactivarUsuario(id) {
    confirmarAccion({
        titulo: 'Desactivar usuario',
        mensaje: 'El usuario no podrá iniciar sesión. ¿Continuar?',
        textoConfirmar: 'Sí, desactivar',
        tipoConfirmar: 'danger',
        onConfirm: async () => {
            try {
                const res = await API.post('usuarios', {
                    action: 'desactivar',
                    id_admin: id,
                });

                if (res.success) {
                    alertify.success(res.message);
                    cargarUsuarios();
                } else {
                    alertify.error(res.message || 'No se pudo desactivar');
                }
            } catch (e) {
                console.error(e);
                alertify.error('Error de conexión');
            }
        },
    });
}

window.abrirModalUsuario = abrirModalUsuario;
window.editarUsuario = editarUsuario;
window.guardarUsuario = guardarUsuario;
window.abrirModalPasswordAdmin = abrirModalPasswordAdmin;
window.guardarPasswordAdmin = guardarPasswordAdmin;
window.desactivarUsuario = desactivarUsuario;
