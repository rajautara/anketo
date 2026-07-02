<!DOCTYPE html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color:#212529; margin:0; padding:16px;">
    <h2 style="margin:0 0 4px;">New submission received</h2>
    <p style="margin:0 0 2px;"><strong><?= esc($formTitle) ?></strong></p>
    <p style="color:#6c757d; margin:0 0 16px;">Submitted <?= esc($submittedAt) ?></p>

    <?php if (empty($answers)) : ?>
        <p style="color:#6c757d;">This submission contained no answers.</p>
    <?php else : ?>
        <table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse; width:100%; max-width:640px;">
            <?php foreach ($answers as $a) : ?>
                <tr style="border-bottom:1px solid #e9ecef;">
                    <td style="vertical-align:top; font-weight:bold; width:40%;"><?= esc($a['label']) ?></td>
                    <td style="vertical-align:top;">
                        <?php if ($a['value'] === '') : ?>
                            &mdash;
                        <?php elseif ($a['isFile']) : ?>
                            <?= esc($a['value']) ?> (file &mdash; view online)
                        <?php else : ?>
                            <?= nl2br(esc($a['value'])) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php endif ?>

    <p style="margin:20px 0 0;">
        <a href="<?= esc($detailUrl) ?>" style="background:#0d6efd; color:#fff; padding:10px 16px; border-radius:4px; text-decoration:none;">View submission</a>
    </p>
    <p style="color:#6c757d; font-size:12px; margin-top:16px;">Or copy this link: <?= esc($detailUrl) ?></p>
</body>
</html>
