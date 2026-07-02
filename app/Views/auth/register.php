<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.register') ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="card">
    <div class="card-body p-4 p-sm-5">
        <h1 class="h4 mb-1"><?= lang('Auth.register') ?></h1>
        <p class="text-muted mb-4">Create your account and build your first form.</p>

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

        <form action="<?= url_to('register') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingEmailInput" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                <label for="floatingEmailInput"><?= lang('Auth.email') ?></label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingUsernameInput" name="username" inputmode="text" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required>
                <label for="floatingUsernameInput"><?= lang('Auth.username') ?></label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPasswordInput" name="password" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.password') ?>" required>
                <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="floatingPasswordConfirmInput" name="password_confirm" inputmode="text" autocomplete="new-password" placeholder="<?= lang('Auth.passwordConfirm') ?>" required>
                <label for="floatingPasswordConfirmInput"><?= lang('Auth.passwordConfirm') ?></label>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg"><?= lang('Auth.register') ?></button>
            </div>

            <p class="text-center text-muted small mb-0"><?= lang('Auth.haveAccount') ?> <a href="<?= url_to('login') ?>"><?= lang('Auth.login') ?></a></p>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
