<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Edit Form - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="ak-page-header">
            <div>
                <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Back to builder</a>
                <h1 class="h4 mb-0">Form settings</h1>
            </div>
            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i> Open builder</a>
        </div>

        <?= $this->include('partials/flash') ?>

        <form action="<?= site_url('forms/' . $form['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="card mb-4">
                <div class="card-header">General</div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= old('title', $form['title']) ?>" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000"><?= old('description', $form['description']) ?></textarea>
                    </div>

                    <div class="row g-3 mb-0">
                        <div class="col-md-6">
                            <label for="submit_button_text" class="form-label">Submit button text</label>
                            <input type="text" class="form-control" id="submit_button_text" name="submit_button_text" value="<?= old('submit_button_text', $form['submit_button_text']) ?>" maxlength="100" placeholder="Submit">
                        </div>
                        <div class="col-md-6">
                            <label for="success_message" class="form-label">Success message</label>
                            <textarea class="form-control" id="success_message" name="success_message" rows="1" maxlength="2000" placeholder="Thank you! Your response has been recorded."><?= old('success_message', $form['success_message']) ?></textarea>
                            <div class="form-text">Shown after someone submits the form.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Email notifications</div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="notify_on_submission" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="notify_on_submission" name="notify_on_submission" value="1"
                               <?= old('notify_on_submission', $form['notify_on_submission']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_submission">Email me when someone submits this form</label>
                    </div>

                    <div class="mb-0">
                        <label for="notification_email" class="form-label">Notification email <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?= old('notification_email', $form['notification_email']) ?>" maxlength="255" placeholder="Defaults to your account email">
                        <div class="form-text">Leave blank to use your account email address.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
