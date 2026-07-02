<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="card">
    <div class="card-body p-4 p-sm-5">
        <h1 class="h4 mb-1"><?= lang('Auth.login') ?></h1>
        <p class="text-muted mb-4">Welcome back — sign in to your account.</p>

        <?php if (session('error') !== null) : ?>
            <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle alert-ico"></i><span><?= esc(session('error')) ?></span></div>
        <?php elseif (session('errors') !== null) : ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle alert-ico"></i>
                <span>
                    <?php if (is_array(session('errors'))) : ?>
                        <?php foreach (session('errors') as $error) : ?>
                            <?= esc($error) ?><br>
                        <?php endforeach ?>
                    <?php else : ?>
                        <?= esc(session('errors')) ?>
                    <?php endif ?>
                </span>
            </div>
        <?php endif ?>

        <?php if (session('message') !== null) : ?>
            <div class="alert alert-success" role="alert"><i class="bi bi-check-circle alert-ico"></i><span><?= esc(session('message')) ?></span></div>
        <?php endif ?>

        <form action="<?= url_to('login') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingEmailInput" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                <label for="floatingEmailInput"><?= lang('Auth.email') ?></label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPasswordInput" name="password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required>
                <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
            </div>

            <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                <div class="form-check mb-3">
                    <input type="checkbox" name="remember" class="form-check-input" id="rememberCheck" <?php if (old('remember')) : ?> checked<?php endif ?>>
                    <label class="form-check-label" for="rememberCheck"><?= lang('Auth.rememberMe') ?></label>
                </div>
            <?php endif; ?>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg"><?= lang('Auth.login') ?></button>
            </div>

            <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                <p class="text-center text-muted small mb-1"><?= lang('Auth.forgotPassword') ?> <a href="<?= url_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a></p>
            <?php endif ?>

            <?php if (setting('Auth.allowRegistration')) : ?>
                <p class="text-center text-muted small mb-0"><?= lang('Auth.needAccount') ?> <a href="<?= url_to('register') ?>"><?= lang('Auth.register') ?></a></p>
            <?php endif ?>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
