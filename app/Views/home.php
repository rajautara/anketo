<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 100px 0;
    }
    .hero-section h1 {
        font-size: 3.5rem;
        font-weight: 700;
    }
    .hero-section p {
        font-size: 1.25rem;
        opacity: 0.9;
    }
    .feature-card {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .feature-icon {
        font-size: 3rem;
        color: #667eea;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="mb-4">Build Beautiful Forms with Ease</h1>
        <p class="mb-5">Create custom forms, collect submissions, and manage your data with our powerful form builder.</p>
        <a href="<?= base_url('/auth/register') ?>" class="btn btn-light btn-lg me-3">Get Started Free</a>
        <a href="<?= base_url('/auth/login') ?>" class="btn btn-outline-light btn-lg">Login</a>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Powerful Features</h2>
                <p class="text-muted">Everything you need to create and manage forms</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <h4 class="card-title">Drag & Drop Builder</h4>
                        <p class="card-text">Create forms easily with our intuitive drag and drop interface. No coding required.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h4 class="card-title">15+ Field Types</h4>
                        <p class="card-text">Choose from text, email, dropdowns, file uploads, and many more field types.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-code-branch"></i>
                        </div>
                        <h4 class="card-title">Conditional Logic</h4>
                        <p class="card-text">Show or hide fields based on user responses for dynamic forms.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h4 class="card-title">Custom Themes</h4>
                        <p class="card-text">Personalize your forms with custom colors, fonts, and styling options.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="card-title">Multi-User Access</h4>
                        <p class="card-text">Collaborate with your team using role-based access control.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 p-4">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <h4 class="card-title">Export Data</h4>
                        <p class="card-text">Download your submissions in CSV format for further analysis.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Ready to Get Started?</h2>
        <p class="mb-4">Create your first form in minutes with AnkeTo.</p>
        <a href="<?= base_url('/auth/register') ?>" class="btn btn-primary btn-lg">Create Free Account</a>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>