<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">

    <title><?= $this->renderSection('title') ?: 'Anketo' ?></title>

    <script>(function(){try{var t=localStorage.getItem('ak-theme')||((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();</script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>">

    <?= $this->renderSection('pageStyles') ?>
</head>
<body>

<nav class="navbar navbar-expand-lg ak-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?= auth()->loggedIn() ? site_url('dashboard') : site_url('/') ?>">
            <?= $this->include('partials/logo') ?>
            <span>Anketo</span>
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto order-lg-2">
            <button type="button" class="ak-theme-toggle" data-ak-theme-toggle aria-label="Toggle color theme" title="Toggle color theme">
                <i class="bi bi-moon-stars-fill"></i>
                <i class="bi bi-sun-fill"></i>
            </button>

            <?php if (auth()->loggedIn()) : ?>
                <div class="dropdown">
                    <button class="btn ak-btn-ghost d-flex align-items-center gap-2 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="ak-avatar"><?= esc(strtoupper(substr(auth()->user()->email ?? 'U', 0, 1))) ?></span>
                        <span class="d-none d-md-inline text-truncate" style="max-width: 12rem;"><?= esc(auth()->user()->email) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted"><?= esc(auth()->user()->email) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= site_url('dashboard') ?>"><i class="bi bi-grid-1x2 me-2"></i>Dashboard</a></li>
                        <?php if (auth()->user()->inGroup('admin')) : ?>
                            <li><a class="dropdown-item" href="<?= site_url('admin/users') ?>"><i class="bi bi-people me-2"></i>Users</a></li>
                        <?php endif ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url_to('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
                <button class="navbar-toggler border-0 p-1 d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            <?php else : ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url_to('login') ?>">Sign in</a>
                <a class="btn btn-sm btn-primary" href="<?= url_to('register') ?>">Get started</a>
            <?php endif ?>
        </div>

        <?php if (auth()->loggedIn()) : ?>
            <div class="collapse navbar-collapse order-lg-1" id="mainNav">
                <ul class="navbar-nav gap-1 mt-2 mt-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link<?= url_is('dashboard') ? ' active' : '' ?>" href="<?= site_url('dashboard') ?>">Dashboard</a>
                    </li>
                    <?php if (auth()->user()->inGroup('admin')) : ?>
                        <li class="nav-item">
                            <a class="nav-link<?= url_is('admin/*') ? ' active' : '' ?>" href="<?= site_url('admin/users') ?>">Users</a>
                        </li>
                    <?php endif ?>
                </ul>
            </div>
        <?php endif ?>
    </div>
</nav>

<main role="main" class="container py-4 py-lg-5">
    <?= $this->renderSection('main') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?= base_url('assets/js/theme.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
