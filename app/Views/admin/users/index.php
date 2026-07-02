<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Users - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="ak-page-header">
    <div>
        <h1 class="h4 mb-0">Users</h1>
        <div class="ak-page-title-sub"><?= count($users) ?> <?= count($users) === 1 ? 'user' : 'users' ?></div>
    </div>
</div>

<?= $this->include('partials/flash') ?>

<div class="ak-table-card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>User</th>
                <th>Groups</th>
                <th>Status</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : ?>
                <?php $isAdmin = in_array('admin', $user['groups'], true); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ak-avatar"><?= esc(strtoupper(substr($user['email'] ?? '?', 0, 1))) ?></span>
                            <span class="fw-semibold"><?= esc($user['email'] ?? '(no email)') ?></span>
                        </div>
                    </td>
                    <td>
                        <?php foreach ($user['groups'] as $group) : ?>
                            <span class="badge text-bg-<?= $group === 'admin' ? 'primary' : 'secondary' ?>"><?= esc($group) ?></span>
                        <?php endforeach ?>
                    </td>
                    <td>
                        <?php if ($user['active']) : ?>
                            <span class="ak-pill ak-pill-success">Active</span>
                        <?php else : ?>
                            <span class="ak-pill ak-pill-muted">Inactive</span>
                        <?php endif ?>
                    </td>
                    <td class="text-muted"><?= esc($user['created_at']) ?></td>
                    <td class="text-end">
                        <form action="<?= site_url('admin/users/' . $user['id'] . '/group') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="make_admin" value="<?= $isAdmin ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $isAdmin ? 'warning' : 'primary' ?>"
                                <?= ($isAdmin && (int) $user['id'] === $currentUserId) ? 'disabled title="You cannot remove your own admin access"' : '' ?>>
                                <?= $isAdmin ? 'Remove admin' : 'Make admin' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
