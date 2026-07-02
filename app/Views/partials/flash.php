<?php if (session('error') !== null) : ?>
    <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
<?php endif ?>

<?php if (session('errors') !== null) : ?>
    <div class="alert alert-danger" role="alert">
        <?php $errors = session('errors') ?>
        <?php if (is_array($errors)) : ?>
            <ul class="mb-0">
                <?php foreach ($errors as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        <?php else : ?>
            <?= esc($errors) ?>
        <?php endif ?>
    </div>
<?php endif ?>

<?php if (session('message') !== null) : ?>
    <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
<?php endif ?>
