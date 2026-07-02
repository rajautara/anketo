<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>New Form - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <h1 class="h4 mb-4">New Form</h1>

        <?= $this->include('partials/flash') ?>

        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('forms') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= old('title') ?>" required maxlength="255" autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= old('description') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create &amp; open builder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
