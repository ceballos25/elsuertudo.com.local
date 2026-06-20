window.getThemeColor = function getThemeColor(varName, fallback = '') {
  return getComputedStyle(document.documentElement).getPropertyValue(varName).trim() || fallback;
};

document.addEventListener('DOMContentLoaded', () => {
  if (typeof alertify !== 'undefined') {
    alertify.set('notifier', 'position', 'top-right');
    alertify.set('notifier', 'delay', 3);
  }

  initSidebarLayout();
  initSidebarSubmenus();
  markSidebarActiveLinks();
});

function initSidebarLayout() {
  const wrapper = document.getElementById('main-wrapper');
  if (!wrapper) return;

  const applyLayout = () => {
    const mini = window.innerWidth < 1200;
    wrapper.classList.toggle('mini-sidebar', mini);
    wrapper.setAttribute('data-sidebartype', mini ? 'mini-sidebar' : 'full');
  };

  applyLayout();
  window.addEventListener('resize', applyLayout);

  document.querySelectorAll('.sidebartoggler').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      wrapper.classList.toggle('show-sidebar');
    });
  });
}

function initSidebarSubmenus() {
  document.querySelectorAll('#sidebarnav a.has-arrow').forEach(toggle => {
    toggle.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();

      const item = toggle.closest('.sidebar-item');
      const submenu = toggle.nextElementSibling;
      if (!item || !submenu?.classList.contains('first-level')) return;

      const opening = !submenu.classList.contains('in');

      document.querySelectorAll('#sidebarnav > li.sidebar-item.selected').forEach(other => {
        if (other === item) return;
        other.classList.remove('selected');
        const otherToggle = other.querySelector(':scope > a.has-arrow');
        const otherMenu = other.querySelector(':scope > ul.first-level');
        if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
        if (otherMenu) otherMenu.classList.remove('in', 'show');
      });

      item.classList.toggle('selected', opening);
      toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
      submenu.classList.toggle('in', opening);
      submenu.classList.toggle('show', opening);
    });
  });

  // En móvil, cerrar sidebar al navegar
  document.querySelectorAll('#sidebarnav a.sidebar-link:not(.has-arrow)').forEach(link => {
    link.addEventListener('click', () => {
      document.getElementById('main-wrapper')?.classList.remove('show-sidebar');
    });
  });
}

function markSidebarActiveLinks() {
  const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';

  document.querySelectorAll('#sidebarnav a.sidebar-link:not(.has-arrow)').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href === currentPath || href.endsWith('/' + currentPath)) {
      link.classList.add('active');
    }
  });

  document.querySelectorAll('#sidebarnav ul.first-level').forEach(submenu => {
    const activeChild = submenu.querySelector('a.sidebar-link.active');
    if (!activeChild) return;

    submenu.classList.add('in', 'show');
    const parentItem = submenu.closest('.sidebar-item');
    const toggle = parentItem?.querySelector(':scope > a.has-arrow');
    if (parentItem) parentItem.classList.add('selected');
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  });
}

let modalMiPassword = null;

function abrirModalMiPassword() {
  const el = document.getElementById('modalMiPassword');
  if (!el) return;

  if (!modalMiPassword) {
    modalMiPassword = new bootstrap.Modal(el);
  }

  document.getElementById('miPasswordActual').value = '';
  document.getElementById('miPasswordNueva').value = '';
  modalMiPassword.show();
}

async function guardarMiPassword() {
  const actual = document.getElementById('miPasswordActual')?.value || '';
  const nueva = document.getElementById('miPasswordNueva')?.value || '';

  if (!actual || nueva.length < 6) {
    alertify.error('Completa la contraseña actual y una nueva de al menos 6 caracteres');
    return;
  }

  try {
    const res = await API.post('usuarios', {
      action: 'cambiar_mi_password',
      password_actual: actual,
      password_nueva: nueva,
    });

    if (res.success) {
      alertify.success(res.message);
      modalMiPassword?.hide();
    } else {
      alertify.error(res.message || 'No se pudo cambiar la contraseña');
    }
  } catch (e) {
    console.error(e);
    alertify.error('Error de conexión');
  }
}

window.abrirModalMiPassword = abrirModalMiPassword;
window.guardarMiPassword = guardarMiPassword;

/** Texto truncado con tooltip para tablas admin */
window.cellTruncate = function cellTruncate(text, emptyText = '-') {
  const raw = String(text ?? '').trim() || emptyText;
  const safe = raw
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/"/g, '&quot;');
  return `<span class="cell-truncate" title="${safe}">${safe}</span>`;
};

/** Nombre de rifa visible (salto de línea, sin ellipsis) */
window.cellRifaName = function cellRifaName(text, emptyText = '-') {
  const raw = String(text ?? '').trim() || emptyText;
  const safe = raw
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/"/g, '&quot;');
  return `<span class="cell-rifa-name">${safe}</span>`;
};

/** Avatar circular para cards móviles admin */
window.adminCardAvatar = function adminCardAvatar(inicial) {
  return `<div class="admin-card-avatar">${inicial}</div>`;
};

/** Chips cantidad + total (ventas / reservas) */
window.renderCardMetaChips = function renderCardMetaChips(cantidad, total) {
  const qty = Number(cantidad) || 0;
  const txt = `${qty} núm${qty === 1 ? '' : 's'}`;
  return `
    <span class="card-meta-chip card-meta-chip--qty">${txt}</span>
    <span class="card-meta-chip card-meta-chip--total">$${Number(total).toLocaleString('es-CO')}</span>`;
};

/** Enlace WhatsApp unificado (móvil + desktop) */
window.renderAdminWhatsAppPhone = function renderAdminWhatsAppPhone(phone, whatsappUrl) {
  const num = String(phone ?? '').trim() || '--';
  const href = whatsappUrl && whatsappUrl !== '#' ? whatsappUrl : '#';
  return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="admin-card-phone admin-phone-link text-decoration-none">
    <i class="ti ti-brand-whatsapp admin-icon-whatsapp"></i><span>${num}</span>
  </a>`;
};

/** Línea vendedor — contenido interno (card móvil) */
window.renderAdminSellerInner = function renderAdminSellerInner(email) {
  const seller = email ?? 'Sistema';
  return `<span><i class="ti ti-user"></i> Vendedor: ${seller}</span>`;
};

/** Línea vendedor — bloque desktop */
window.renderAdminSellerLine = function renderAdminSellerLine(email) {
  return `<div class="admin-card-extra admin-seller-line">${renderAdminSellerInner(email)}</div>`;
};

/** Cabecera móvil unificada para tablas admin */
window.renderAdminMobileCardHead = function renderAdminMobileCardHead(opts) {
  const name = opts.name || '--';
  const phoneBlock = opts.phoneHtml
    || `<span class="admin-card-phone">${opts.phone || '--'}</span>`;
  const extra = opts.extraLine ? `<div class="admin-card-extra">${opts.extraLine}</div>` : '';
  const meta = opts.metaHtml
    ? `<div class="admin-card-head__meta">${opts.metaHtml}</div>` : '';
  const rifa = opts.rifaHtml
    ? `<div class="admin-card-head__rifa">${opts.rifaHtml}</div>` : '';
  const code = opts.codeHtml
    ? `<div class="admin-card-head__code">${opts.codeHtml}</div>` : '';
  const status = opts.statusHtml
    ? `<div class="admin-card-head__status-row">${opts.statusHtml}</div>` : '';
  const numbers = opts.numbersHtml
    ? `<div class="admin-card-head__numeros">${opts.numbersHtml}</div>` : '';

  return `
    <div class="admin-card-head d-lg-none">
      <div class="admin-card-head__client d-flex align-items-center gap-2 min-w-0">
        ${adminCardAvatar(opts.inicial || 'C')}
        <div class="admin-card-client-info min-w-0 flex-grow-1">
          <span class="admin-card-name">${name}</span>
          ${phoneBlock}
          ${extra}
        </div>
      </div>
      ${status}
      ${meta}
      ${rifa}
      ${numbers}
      ${code}
    </div>`;
};

/** Cliente desktop unificado (ventas / reservas) */
window.renderAdminDesktopClient = function renderAdminDesktopClient(opts) {
  const phoneBlock = opts.phoneHtml
    || `<div class="small text-muted">${opts.phone || '--'}</div>`;
  const extra = opts.extraHtml || '';
  return `
    <div class="d-none d-lg-flex align-items-center gap-2 min-w-0 admin-desktop-client">
      ${adminCardAvatar(opts.inicial || 'C')}
      <div class="min-w-0">
        <div class="admin-client-name fw-bold text-capitalize cell-truncate">${opts.name || '--'}</div>
        ${phoneBlock}
        ${extra}
      </div>
    </div>`;
};

/** Columna Nums/Rifa desktop (ventas / reservas) */
window.renderRifaColumnDesktop = function renderRifaColumnDesktop(cantidad, rifaTitle, tickets) {
  const qty = Number(cantidad) || 0;
  const nums = tickets?.length
    ? `<div class="mt-1">${renderAdminNumBadges(tickets)}</div>`
    : '';
  return `
    <span class="fw-medium text-dark d-block">${qty} Núms</span>
    ${cellRifaName(rifaTitle)}
    ${nums}`;
};

/** Acciones móvil estilo ventas (btn-group ancho completo) */
window.renderAdminMobileActions = function renderAdminMobileActions(buttonsHtml) {
  return `
    <div class="btn-group btn-group-sm shadow-sm w-100" role="group">
      ${buttonsHtml}
    </div>`;
};

/** Números / tickets en listas admin (mismo estilo en todo el proyecto) */
window.renderAdminNumBadges = function renderAdminNumBadges(numbers, maxVisible = 12) {
  if (!numbers?.length) {
    return '<span class="text-muted small">Sin números</span>';
  }
  const visible = numbers.slice(0, maxVisible);
  const rest = numbers.length - visible.length;
  const chips = visible.map(n => `<span class="admin-num-chip admin-num-chip--sold">${n}</span>`).join('');
  const more = rest > 0
    ? `<span class="badge bg-secondary" title="${numbers.slice(maxVisible).join(', ')}">+${rest}</span>`
    : '';
  return `<div class="card-chip-list">${chips}${more}</div>`;
};

/** Token / código en card móvil */
window.renderAdminCodeChip = function renderAdminCodeChip(code) {
  const safe = String(code ?? '').trim();
  if (!safe) return '';
  return `<span class="token-chip token-chip-block" title="${safe.replace(/"/g, '&quot;')}">${safe}</span>`;
};
