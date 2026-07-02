<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>My account - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="ak-page-header">
            <div>
                <h1 class="h4 mb-0">My account</h1>
                <div class="ak-page-title-sub">Manage your email and password</div>
            </div>
        </div>

        <?= $this->include('partials/flash') ?>

        <div class="card mb-4">
            <div class="card-header">Profile</div>
            <div class="card-body p-4">
                <form action="<?= site_url('account/profile') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email', $user->email) ?>" required maxlength="254" autocomplete="email">
                        <div class="form-text">You sign in with this email address.</div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save email</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Password</div>
            <div class="card-body p-4">
                <form action="<?= site_url('account/password') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                            <div class="form-text">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_password_confirm" class="form-label">Confirm new password</label>
                            <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i> Change password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
