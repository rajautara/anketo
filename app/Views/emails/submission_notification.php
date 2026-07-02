<!DOCTYPE html>
<html>
<body style="margin:0; padding:24px 16px; background:#f7f8fc; font-family:'Segoe UI',Helvetica,Arial,sans-serif; color:#0f172a;">
    <table cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e6e8f0; border-radius:14px; overflow:hidden;">
        <tr><td style="height:6px; background:linear-gradient(90deg,#6366f1,#8b5cf6); line-height:6px; font-size:0;">&nbsp;</td></tr>
        <tr>
            <td style="padding:24px 28px;">
                <p style="margin:0 0 2px; font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:#6366f1; font-weight:700;">New submission</p>
                <h1 style="margin:0 0 4px; font-size:20px; color:#0b1220;"><?= esc($formTitle) ?></h1>
                <p style="color:#64748b; margin:0 0 20px; font-size:13px;">Submitted <?= esc($submittedAt) ?></p>

                <?php if (empty($answers)) : ?>
                    <p style="color:#64748b;">This submission contained no answers.</p>
                <?php else : ?>
                    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; width:100%;">
                        <?php foreach ($answers as $a) : ?>
                            <tr>
                                <td style="vertical-align:top; font-weight:600; color:#64748b; font-size:13px; width:38%; padding:10px 12px 10px 0; border-bottom:1px solid #eef1f6;"><?= esc($a['label']) ?></td>
                                <td style="vertical-align:top; color:#0f172a; font-size:14px; padding:10px 0; border-bottom:1px solid #eef1f6;">
                                    <?php if ($a['value'] === '') : ?>
                                        <span style="color:#94a3b8;">&mdash;</span>
                                    <?php elseif ($a['isFile']) : ?>
                                        <?= esc($a['value']) ?> <span style="color:#94a3b8;">(file &mdash; view online)</span>
                                    <?php else : ?>
                                        <?= nl2br(esc($a['value'])) ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                <?php endif ?>

                <p style="margin:24px 0 0;">
                    <a href="<?= esc($detailUrl) ?>" style="display:inline-block; background:#6366f1; color:#ffffff; padding:11px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px;">View submission</a>
                </p>
                <p style="color:#94a3b8; font-size:12px; margin-top:16px;">Or copy this link: <?= esc($detailUrl) ?></p>
            </td>
        </tr>
    </table>
    <p style="text-align:center; color:#94a3b8; font-size:12px; margin:16px 0 0;">Powered by Anketo</p>
</body>
</html>
