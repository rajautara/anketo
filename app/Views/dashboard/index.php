<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.5rem;
    }
    .stat-icon.primary {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    .stat-icon.success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    .stat-icon.warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    .stat-icon.info {
        background-color: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
    }
    .form-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .form-card:hover {
        transform: translateY(-3px);
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Dashboard</h2>
            <p class="text-muted">Welcome back, <?= esc(session()->get('user_name')) ?>!</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon primary me-3">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $stats['total_forms'] ?></h3>
                        <p class="mb-0 text-muted">Total Forms</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon success me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $stats['published_forms'] ?></h3>
                        <p class="mb-0 text-muted">Published</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon warning me-3">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $stats['draft_forms'] ?></h3>
                        <p class="mb-0 text-muted">Drafts</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon info me-3">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold"><?= $new_submissions_count ?></h3>
                        <p class="mb-0 text-muted">New Submissions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Forms -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Your Forms</h5>
                    <a href="<?= base_url('/forms/create') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Create Form
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($forms)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <h5>No forms yet</h5>
                        <p class="text-muted">Create your first form to get started</p>
                        <a href="<?= base_url('/forms/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Form
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach (array_slice($forms, 0, 6) as $form): ?>
                        <div class="col-md-6">
                            <div class="card form-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title fw-bold mb-0"><?= esc($form['title']) ?></h6>
                                        <span class="badge status-badge bg-<?= $form['status'] === 'published' ? 'success' : ($form['status'] === 'draft' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($form['status']) ?>
                                        </span>
                                    </div>
                                    <p class="card-text text-muted small mb-3"><?= esc(substr($form['description'] ?? 'No description', 0, 80)) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= date('M d, Y', strtotime($form['created_at'])) ?>
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('/forms/' . $form['id'] . '/builder') ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('/forms/' . $form['id'] . '/preview') ?>" class="btn btn-outline-secondary" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= base_url('/submissions/form/' . $form['id']) ?>" class="btn btn-outline-info" title="Submissions">
                                                <i class="fas fa-inbox"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($forms) > 6): ?>
                    <div class="text-center mt-3">
                        <a href="<?= base_url('/forms') ?>" class="btn btn-outline-primary">View All Forms</a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_submissions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No submissions yet</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($recent_submissions, 0, 5) as $submission): ?>
                        <a href="<?= base_url('/submissions/' . $submission['id']) ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= esc($submission['form_title']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?= date('M d, H:i', strtotime($submission['submitted_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($submission['status'] === 'new'): ?>
                                <span class="badge bg-primary">New</span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('/forms/create') ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Form
                        </a>
                        <a href="<?= base_url('/submissions') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-inbox me-2"></i>View All Submissions
                        </a>
                        <?php if (session()->get('role_name') === 'admin'): ?>
                        <a href="<?= base_url('/admin') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>