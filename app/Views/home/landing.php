<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Anketo — Build forms people love to fill out<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/public-form.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/public-form.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('main') ?>

<section class="ak-hero">
    <span class="ak-eyebrow"><i class="bi bi-stars"></i> Self-hosted form builder</span>
    <h1>Build forms people<br><span class="ak-hero-accent">love to fill out</span></h1>
    <p class="ak-hero-lead">
        Anketo is a drag-and-drop form &amp; survey builder. Design a form in minutes,
        share a link, and collect responses — with CSV export and email alerts built in.
    </p>
    <div class="ak-hero-cta">
        <a href="<?= url_to('register') ?>" class="btn btn-primary btn-lg"><i class="bi bi-rocket-takeoff me-2"></i>Get started free</a>
        <a href="<?= url_to('login') ?>" class="btn btn-outline-secondary btn-lg">Sign in</a>
    </div>

    <div class="ak-mock" aria-hidden="true">
        <div class="ak-mock-bar">
            <span class="ak-mock-dot"></span><span class="ak-mock-dot"></span><span class="ak-mock-dot"></span>
            <div class="ak-skel ms-3" style="height:.7rem;width:38%;"></div>
        </div>
        <div class="ak-mock-body">
            <div class="ak-skel mb-3" style="height:1.1rem;width:45%;"></div>
            <div class="ak-skel mb-4" style="height:.7rem;width:70%;"></div>
            <div class="row g-3">
                <div class="col-12"><div class="ak-skel" style="height:2.6rem;"></div></div>
                <div class="col-12"><div class="ak-skel" style="height:2.6rem;"></div></div>
                <div class="col-6"><div class="ak-skel" style="height:2.6rem;"></div></div>
                <div class="col-6"><div class="ak-skel" style="height:2.6rem;"></div></div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <span class="btn btn-primary btn-sm disabled">Submit</span>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="ak-feature">
                <span class="ak-icon-box"><i class="bi bi-ui-checks-grid"></i></span>
                <h3>Drag-and-drop builder</h3>
                <p>Nine field types — text, choices, dropdowns, dates, file uploads and more. Arrange them by dragging, no code required.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ak-feature">
                <span class="ak-icon-box"><i class="bi bi-link-45deg"></i></span>
                <h3>Share anywhere</h3>
                <p>Publish your form and share a public link. Anyone can respond — no login or account needed on their side.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ak-feature">
                <span class="ak-icon-box"><i class="bi bi-inbox"></i></span>
                <h3>Collect &amp; export</h3>
                <p>Review every submission, download responses as CSV, and get an email the moment someone completes your form.</p>
            </div>
        </div>
    </div>
</section>

<section class="ak-landing-footer text-center">
    <div class="d-inline-flex align-items-center gap-2 mb-2">
        <?= $this->include('partials/logo') ?>
        <span class="fw-bold">Anketo</span>
    </div>
    <p class="mb-0 small">&copy; <?= date('Y') ?> Anketo · A lightweight, self-owned form builder.</p>
</section>

<?= $this->endSection() ?>
