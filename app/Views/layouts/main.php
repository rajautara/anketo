<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">

    <title><?= $this->renderSection('title') ?: 'Anketo' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= auth()->loggedIn() ? site_url('dashboard') : site_url('/') ?>">Anketo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (auth()->loggedIn()) : ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('dashboard') ?>">Dashboard</a>
                    </li>
                    <?php if (auth()->user()->inGroup('admin')) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('admin/users') ?>">Users</a>
                        </li>
                    <?php endif ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link text-white-50"><?= esc(auth()->user()->email) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url_to('logout') ?>">Logout</a>
                    </li>
                </ul>
            <?php else : ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url_to('login') ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url_to('register') ?>">Register</a>
                    </li>
                </ul>
            <?php endif ?>
        </div>
    </div>
</nav>

<main role="main" class="container pb-5">
    <?= $this->renderSection('main') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
