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

                    <hr class="my-4">
                    <h2 class="h6 text-muted mb-3">Email notifications</h2>

                    <div class="form-check mb-3">
                        <input type="hidden" name="notify_on_submission" value="0">
                        <input class="form-check-input" type="checkbox" id="notify_on_submission" name="notify_on_submission" value="1"
                               <?= old('notify_on_submission', $form['notify_on_submission']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notify_on_submission">Email me when someone submits this form</label>
                    </div>

                    <div class="mb-3">
                        <label for="notification_email" class="form-label">Notification email <span class="text-muted">(optional)</span></label>
                        <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?= old('notification_email', $form['notification_email']) ?>" maxlength="255" placeholder="Defaults to your account email">
                        <div class="form-text">Leave blank to use your account email address.</div>
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
