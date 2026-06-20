<?php
require_once __DIR__ . '/config/config.php';

use App\Core\SiteConfig;

$siteName = SiteConfig::name();
$siteLogo = SiteConfig::logo();
$siteFavicon = SiteConfig::favicon();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($siteName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Reserva tus números de la suerte — <?= htmlspecialchars($siteName) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme-base.css?v=11">
    <link rel="stylesheet" href="assets/css/front.css?v=48">
    <link rel="shortcut icon" href="<?= htmlspecialchars($siteFavicon) ?>" type="image/x-icon">
</head>

<body class="landing-page">

<!-- HEADER -->
<header class="landing-header sticky-top">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center py-3">
            <a href="#" class="landing-brand text-decoration-none d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="landing-logo">
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="#" class="btn btn-sm btn-outline-primary rounded-circle landing-social d-none" data-facebook aria-label="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="#" class="btn btn-sm btn-outline-danger rounded-circle landing-social d-none" data-instagram aria-label="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="#" class="btn btn-sm btn-success rounded-circle landing-social-btn d-none" data-whatsapp aria-label="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<main>

<!-- HERO / INFO RIFA -->
<section class="container landing-section">
    <div class="card landing-hero-card border-0 shadow-sm overflow-hidden">
        <div class="landing-hero-accent"></div>
        <div class="card-body text-center py-4 px-4">

            <div id="infoSkeleton">
                <div class="skeleton skeleton-title mb-3"></div>
                <div class="skeleton skeleton-text mx-auto mb-2" style="width:40%"></div>
                <div class="d-flex justify-content-center gap-3 mt-3 mb-3">
                    <div class="skeleton skeleton-badge"></div>
                    <div class="skeleton skeleton-badge"></div>
                </div>
            </div>

            <div class="real-content d-none">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 mb-3 fw-semibold">
                <i class="bi bi-circle-fill ms-1 me-2"></i> Evento activado
                </span>
                <h1 class="h3 fw-bold mb-2 landing-title" id="raffleTitle"></h1>
                <p class="text-muted mb-3 landing-subtitle">
                    Juega con <strong id="premioLoteria"></strong>
                </p>
                <p class="text-muted small mb-4">
                    <i class="bi bi-calendar-event me-1 text-success"></i>
                    <span id="raffleDate"></span>
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <span class="badge landing-stat-badge landing-stat-badge--price px-3 py-2 rounded-pill">
                        <i class="bi bi-currency-dollar me-1"></i>
                        <span id="rafflePrice"></span>
                    </span>
                    <span class="badge landing-stat-badge landing-stat-badge--digits px-3 py-2 rounded-pill">
                        <i class="bi bi-hash me-1"></i>
                        <span id="raffleDigits"></span>
                    </span>
                </div>
            </div>

        </div>
    </div>

    <!-- PREMIOS -->
    <section class="landing-section-sm">
        <div class="text-center mb-4">
            <h2 class="h5 fw-bold mb-1 landing-section-heading">
                <i class="bi bi-gift-fill me-2"></i>Premios del día
            </h2>
            <p class="text-muted small mb-0">Conoce lo que puedes ganar hoy</p>
        </div>
        <div id="premiosSkeleton" class="row g-3">
            <div class="col-12"><div class="skeleton" style="height: 100px;"></div></div>
        </div>
        <div class="row g-3 real-content d-none" id="premiosWrapper"></div>
    </section>
</section>

<!-- NUMEROS -->
<section id="numbers" class="container landing-section pb-2 pb-lg-0">

    <div id="rafflePausedBanner" class="alert alert-warning border-0 shadow-sm text-center fw-semibold mb-4 d-none rounded-4 py-3 landing-paused-banner" role="alert">
        <i class="bi bi-pause-circle-fill me-2"></i>
        Rifa temporalmente detenida — Por ahora no se pueden reservar números.
    </div>

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card landing-numbers-card border-0 shadow-sm">

                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1 landing-section-heading">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i>Elige tus números
                            </h2>
                            <p class="text-muted small mb-0" id="numbersSectionHint">Toca un número disponible para seleccionarlo</p>
                        </div>
                        <button class="btn btn-outline-success btn-sm rounded-pill px-3" onclick="pickRandom()">
                            <i class="bi bi-shuffle me-1"></i>Elegir por mí
                        </button>
                    </div>

                    <div class="landing-availability mb-3">
                        <div class="d-flex justify-content-between align-items-center small mb-2">
                            <span class="text-muted">
                                <i class="bi bi-check-circle text-success me-1"></i>
                                Disponibles <strong id="availableCount" class="text-success">0</strong>
                                <span class="text-muted">/</span>
                                <strong id="totalCount">0</strong>
                            </span>
                            <span class="text-muted" id="availabilityPercent">0%</span>
                        </div>
                        <div class="progress landing-progress" role="progressbar" aria-label="Progreso de ventas">
                            <div class="progress-bar bg-success" id="availabilityProgress" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 small text-muted landing-legend pb-2">
                        <span><span class="landing-legend-dot landing-legend-free"></span> Disponible</span>
                        <span><span class="landing-legend-dot landing-legend-selected"></span> Seleccionado</span>
                        <span><span class="landing-legend-dot landing-legend-sold"></span> Vendido / Reservado</span>
                    </div>
                </div>

                <div class="card-body px-3 px-md-4 mb-2">
                    <div id="numbersGrid" class="row g-2 mx-0">
                        <script>
                            for (let i = 0; i < 24; i++) {
                                document.write(`
                                <div class="col-3 col-sm-2 px-1">
                                    <div class="skeleton skeleton-box"></div>
                                </div>
                                `);
                            }
                        </script>
                    </div>
                    <ul class="pagination justify-content-center mt-4 mb-2" id="paginacionContainer"></ul>
                </div>

            </div>
        </div>

        <!-- CHECKOUT DESKTOP -->
        <aside class="col-lg-4 d-none d-lg-block">
            <div class="card landing-checkout-card border-0 shadow sticky-top">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="landing-checkout-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-cart-check text-success"></i>
                        </span>
                        <h2 class="h5 fw-bold mb-0">Tu compra</h2>
                    </div>

                    <div class="landing-checkout-summary rounded-4 p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">
                                <i class="bi bi-ticket-perforated me-1"></i>Cantidad
                            </span>
                            <strong class="fs-5" id="checkoutQty">0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top js-checkout-total-block">
                            <span class="fw-semibold">Total</span>
                            <strong class="fs-4 text-success">$<span id="checkoutTotal">0</span></strong>
                        </div>
                    </div>

                    <button id="landingCheckoutBtn" class="btn btn-success w-100 fw-bold rounded-pill py-3 landing-btn-primary" onclick="abrirCheckoutModal()">
                        <i class="bi bi-credit-card me-2"></i>Pagar ahora
                    </button>

                    <p id="landingCheckoutNote" class="text-muted text-center small mt-3 mb-0">
                        <i class="bi bi-shield-check me-1"></i>Reserva segura vía WhatsApp
                    </p>
                </div>
            </div>
        </aside>

    </div>
</section>

</main>

<!-- MODAL DATOS CLIENTE -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <div class="modal-header border-0 bg-success-subtle px-4 pt-4 pb-3">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="checkoutModalTitle">
                        <i class="bi bi-receipt-cutoff text-success me-2"></i>Confirmar compra
                    </h5>
                    <p class="text-muted small mb-0" id="checkoutModalSubtitle">Completa tus datos para reservar</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body px-4 pt-3 pb-2">

                <div class="mb-3">
                    <label for="clientPhone" class="form-label fw-semibold small">
                        <i class="bi bi-phone me-1 text-muted"></i>Celular (WhatsApp)
                    </label>
                    <input type="tel" id="clientPhone" class="form-control form-control-lg rounded-3" autocomplete="tel" inputmode="numeric" maxlength="10" >
                    <div id="clientPhoneStatus" class="small mt-2 d-none"></div>
                </div>

                <div class="mb-4">
                    <label for="clientName" class="form-label fw-semibold small">
                        <i class="bi bi-person me-1 text-muted"></i>Nombre completo
                    </label>
                    <input type="text" id="clientName" class="form-control form-control-lg rounded-3 text-capitalize" autocomplete="name">
                </div>

                <div class="border rounded-4 p-3 landing-checkout-summary">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-ticket-perforated me-1"></i>Tus números
                        </small>
                        <strong id="modalNumbers" class="landing-modal-numbers">---</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top js-checkout-total-block">
                        <span class="fw-semibold">Total a pagar</span>
                        <span class="fw-bold fs-4 text-success">
                            $<span id="modalTotal">0</span>
                        </span>
                    </div>
                </div>

            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2">
                <button class="btn btn-outline-secondary rounded-pill flex-fill" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button class="btn btn-success fw-bold rounded-pill flex-fill" onclick="confirmarReserva()">
                    <i class="bi bi-check-circle me-1"></i>Confirmar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MOBILE CHECKOUT -->
<div id="mobileCheckout" class="mobile-checkout d-lg-none translate-bottom">
    <div class="checkout-info">
        <small class="label">Cantidad</small>
        <strong class="value" id="checkoutQtyMobile">0</strong>
    </div>
    <div class="checkout-info text-end js-checkout-total-block">
        <small class="label">Total</small>
        <strong class="value text-success">$<span id="checkoutTotalMobile">0</span></strong>
    </div>
    <button
        id="landingCheckoutBtnMobile"
        class="btn btn-success btn-checkout-mobile fw-bold"
        onclick="abrirCheckoutModal()"
        id="btnCheckoutMobile"
    >
        <i class="bi bi-arrow-right-circle me-1"></i>Continuar
    </button>
</div>

<!-- MODAL ALERTA UI -->
<div class="modal fade" id="uiAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4 border-0 shadow-lg rounded-4">
            <div class="mb-3">
                <span class="alert-icon fs-1 d-inline-flex align-items-center justify-content-center rounded-circle landing-alert-icon"></span>
            </div>
            <h5 class="modal-title fw-bold mb-2"></h5>
            <div class="modal-body text-muted p-0 small lh-base"></div>
            <div class="mt-4">
                <button type="button" class="btn btn-success px-4 py-2 rounded-pill w-100" data-bs-dismiss="modal">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL INFO TICKET -->
<div class="modal fade" id="ticketInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4 px-4">
                <div
                    id="ticketAvatar"
                    class="d-flex align-items-center justify-content-center mx-auto text-white fw-bold fs-3 rounded-circle mb-3 shadow-sm landing-ticket-avatar"
                ></div>
                <h5 class="fw-bold mb-2" id="ticketClientName">---</h5>
                <div class="mb-3">
                    <span id="ticketStatus" class="badge rounded-pill px-3 py-2"></span>
                </div>
                <div class="text-muted small border-top pt-3 mt-2">
                    <i class="bi bi-calendar-check me-1"></i>
                    <span id="ticketDate" class="fw-semibold">---</span>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-success px-4 py-2 rounded-pill w-100" data-bs-dismiss="modal">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL MANTENIMIENTO -->
<div class="modal fade" id="siteMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-5">
                <div class="landing-maintenance-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                    <i class="bi bi-tools fs-1 text-warning"></i>
                </div>
                <h4 class="fw-bold mb-2">Sitio en mantenimiento</h4>
                <p class="text-muted mb-0">
                    Estamos realizando mejoras para brindarte una mejor experiencia.<br>
                    Vuelve a intentarlo más tarde.
                </p>
            </div>
        </div>
    </div>
</div>

<footer class="landing-footer mt-5">
    <?php
    $contactCountry = env('CONTACT_COUNTRY', 'Colombia');
    $devName = env('DEVELOPER_NAME', 'Cristian Ceballos');
    $devUrl = env('DEVELOPER_URL', 'https://rifacloud-landing.cristianceballos.com/');
    ?>
    <div class="container landing-footer__inner">
        <div class="landing-footer__bottom">
            <p class="landing-footer__copy mb-2 small">
                © <?= date('Y') ?> <strong><?= htmlspecialchars($siteName) ?></strong> · <?= htmlspecialchars($contactCountry) ?>. Todos los derechos reservados.
            </p>
            <p class="landing-footer__legal small mb-2">
                Participa responsablemente. <?= htmlspecialchars($siteName) ?> promueve transparencia y confianza en cada actividad.
            </p>
            <p class="landing-footer__dev small mb-0">
                Desarrollado por
                <a href="<?= htmlspecialchars($devUrl) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="landing-footer__dev-link">
                    <?= htmlspecialchars($devName) ?> <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                </a>
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once 'includes/pagination_script.php'; ?>
<script>window.SITE_URL = <?= json_encode(BASE_URL) ?>; window.API_URL = <?= json_encode(BASE_URL . '/front/ajax/api.php') ?>;</script>
<script src="assets/js/api.js?v=3"></script>
<script src="assets/js/raffle.js?v=16"></script>

</body>
</html>
