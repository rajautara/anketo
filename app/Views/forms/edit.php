<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Edit Form - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Edit Form</h1>
            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary">Open builder</a>
        </div>

        <?= $this->include('partials/flash') ?>

        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('forms/' . $form['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= old('title', $form['title']) ?>" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= old('description', $form['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="submit_button_text" class="form-label">Submit button text</label>
                        <input type="text" class="form-control" id="submit_button_text" name="submit_button_text" value="<?= old('submit_button_text', $form['submit_button_text']) ?>" maxlength="100" placeholder="Submit">
                    </div>

                    <div class="mb-3">
                        <label for="success_message" class="form-label">Success message <span class="text-muted">(shown after submitting)</span></label>
                        <textarea class="form-control" id="success_message" name="success_message" rows="2" maxlength="2000" placeholder="Thank you! Your response has been recorded."><?= old('success_message', $form['success_message']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
