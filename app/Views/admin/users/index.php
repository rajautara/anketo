<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Users - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<h1 class="h4 mb-4">Users</h1>

<?= $this->include('partials/flash') ?>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white">
        <thead>
            <tr>
                <th>Email</th>
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
                    <td><?= esc($user['email'] ?? '(no email)') ?></td>
                    <td>
                        <?php foreach ($user['groups'] as $group) : ?>
                            <span class="badge text-bg-<?= $group === 'admin' ? 'primary' : 'secondary' ?>"><?= esc($group) ?></span>
                        <?php endforeach ?>
                    </td>
                    <td>
                        <?= $user['active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
                    </td>
                    <td><?= esc($user['created_at']) ?></td>
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
