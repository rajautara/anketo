<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Add user - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="ak-page-header">
            <div>
                <a href="<?= site_url('admin/users') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Users</a>
                <h1 class="h4 mb-0">Add user</h1>
            </div>
        </div>

        <?= $this->include('partials/flash') ?>

        <div class="card">
            <div class="card-body p-4">
                <form action="<?= site_url('admin/users') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required maxlength="254" autofocus autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <div class="form-text">At least 8 characters. Share it with the user securely.</div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?= old('role') === 'admin' ? '' : 'selected' ?>>User — manages their own forms</option>
                            <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin — full access + user management</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= site_url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Create user</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
