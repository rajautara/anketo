<?php if (session('error') !== null) : ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle-fill alert-ico"></i>
        <span><?= esc(session('error')) ?></span>
    </div>
<?php endif ?>

<?php if (session('errors') !== null) : ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle-fill alert-ico"></i>
        <?php $errors = session('errors') ?>
        <?php if (is_array($errors)) : ?>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        <?php else : ?>
            <span><?= esc($errors) ?></span>
        <?php endif ?>
    </div>
<?php endif ?>

<?php if (session('message') !== null) : ?>
    <div class="alert alert-success" role="alert">
        <i class="bi bi-check-circle-fill alert-ico"></i>
        <span><?= esc(session('message')) ?></span>
    </div>
<?php endif ?>
