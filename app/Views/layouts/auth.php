<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?= $this->renderSection('title') ?: 'Anketo' ?></title>

    <script>(function(){try{var t=localStorage.getItem('ak-theme')||((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/public-form.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/public-form.css') ?>">

    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="ak-public-body">

<div class="position-absolute top-0 end-0 p-3">
    <button type="button" class="ak-theme-toggle" data-ak-theme-toggle aria-label="Toggle color theme" title="Toggle color theme">
        <i class="bi bi-moon-stars-fill"></i>
        <i class="bi bi-sun-fill"></i>
    </button>
</div>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <a href="<?= site_url('/') ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-4 navbar-brand">
                <?= $this->include('partials/logo') ?>
                <span class="fs-5 fw-bold">Anketo</span>
            </a>

            <?= $this->renderSection('main') ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?= base_url('assets/js/theme.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/theme.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
