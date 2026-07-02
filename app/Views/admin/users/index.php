<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Users - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="ak-page-header">
    <div>
        <h1 class="h4 mb-0">Users</h1>
        <div class="ak-page-title-sub"><?= count($users) ?> <?= count($users) === 1 ? 'user' : 'users' ?></div>
    </div>
    <a href="<?= site_url('admin/users/new') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Add user
    </a>
</div>

<?= $this->include('partials/flash') ?>

<div class="ak-table-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Groups</th>
                    <th class="d-none d-md-table-cell">Status</th>
                    <th class="d-none d-lg-table-cell">Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <?php $isAdmin = in_array('admin', $user['groups'], true); ?>
                    <?php $isSelf = (int) $user['id'] === $currentUserId; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="ak-avatar"><?= esc(strtoupper(substr($user['email'] ?? '?', 0, 1))) ?></span>
                                <div>
                                    <span class="fw-semibold"><?= esc($user['email'] ?? '(no email)') ?></span>
                                    <?php if ($isSelf) : ?><span class="text-muted small">(you)</span><?php endif ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php foreach ($user['groups'] as $group) : ?>
                                <span class="badge text-bg-<?= $group === 'admin' ? 'primary' : 'secondary' ?>"><?= esc($group) ?></span>
                            <?php endforeach ?>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <?php if ($user['active']) : ?>
                                <span class="ak-pill ak-pill-success">Active</span>
                            <?php else : ?>
                                <span class="ak-pill ak-pill-muted">Inactive</span>
                            <?php endif ?>
                        </td>
                        <td class="text-muted d-none d-lg-table-cell"><?= esc($user['created_at']) ?></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2 align-items-center">
                                <form action="<?= site_url('admin/users/' . $user['id'] . '/group') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="make_admin" value="<?= $isAdmin ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $isAdmin ? 'warning' : 'primary' ?>"
                                        <?= ($isAdmin && $isSelf) ? 'disabled title="You cannot remove your own admin access"' : '' ?>>
                                        <?= $isAdmin ? 'Remove admin' : 'Make admin' ?>
                                    </button>
                                </form>
                                <form action="<?= site_url('admin/users/' . $user['id'] . '/delete') ?>" method="post" class="d-inline"
                                      onsubmit="return confirm('Delete this user? This permanently removes their account and all of their forms and submissions.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete user"
                                        <?= $isSelf ? 'disabled title="You cannot delete your own account"' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
