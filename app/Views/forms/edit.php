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

        <?php if ($access['canManageCollaborators']) : ?>
            <div class="card mt-4 mb-4">
                <div class="card-header">Collaborators</div>
                <div class="card-body p-4">
                    <?php if (empty($availableUsers)) : ?>
                        <p class="text-muted mb-0">No other users are available to assign.</p>
                    <?php else : ?>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="collaborator_user_id" class="form-label">User</label>
                                <select class="form-select" id="collaborator_user_id" name="collaborator_user_id" form="collaborator-form">
                                    <?php foreach ($availableUsers as $user) : ?>
                                        <option value="<?= (int) $user['id'] ?>"><?= esc($user['email']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="collaborator_form_access" class="form-label">Form access</label>
                                <select class="form-select" id="collaborator_form_access" name="form_access" form="collaborator-form">
                                    <option value="none">None</option>
                                    <option value="view">View</option>
                                    <option value="edit">Edit</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="collaborator_submission_access" class="form-label">Results access</label>
                                <select class="form-select" id="collaborator_submission_access" name="submission_access" form="collaborator-form">
                                    <option value="none">None</option>
                                    <option value="view">View</option>
                                    <option value="export">Export</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="submit" class="btn btn-outline-primary" form="collaborator-form" title="Save collaborator">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif ?>

                    <?php if (! empty($collaborators)) : ?>
                        <div class="table-responsive mt-4">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Form</th>
                                        <th>Results</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($collaborators as $collaborator) : ?>
                                        <tr>
                                            <td><?= esc($collaborator['email']) ?></td>
                                            <td><span class="badge text-bg-light border"><?= esc($collaborator['form_access']) ?></span></td>
                                            <td><span class="badge text-bg-light border"><?= esc($collaborator['submission_access']) ?></span></td>
                                            <td class="text-end">
                                                <form action="<?= site_url('forms/' . $form['id'] . '/collaborators/' . $collaborator['id'] . '/delete') ?>" method="post" class="d-inline" onsubmit="return confirm('Remove collaborator access?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <form id="collaborator-form" action="<?= site_url('forms/' . $form['id'] . '/collaborators') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="">
            </form>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('collaborator-form');
                var userSelect = document.getElementById('collaborator_user_id');
                if (!form || !userSelect) { return; }
                form.addEventListener('submit', function () {
                    form.querySelector('[name="user_id"]').value = userSelect.value;
                });
            });
            </script>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
