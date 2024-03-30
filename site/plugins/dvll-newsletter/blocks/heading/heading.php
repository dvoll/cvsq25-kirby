<?php
$fontSize = 20;
$fontWeight = 'normal';
switch ($block->level()) {
    case 'h1':
        $fontSize = 25;
        break;
    case 'h3':
        $fontSize = 16;
        $fontWeight = 'bold';
        break;
    case 'h4':
        $fontSize = 16;
        break;
}
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; padding-top: 20px;">
            <?php /** @var \Kirby\Cms\Block $block */ ?>
            <<?= $level = $block->level()->or('h2') ?> style="margin: 0 0 10px 0; font-family: sans-serif; font-size: <?= $fontSize ?>px; line-height: <?= $fontSize + 5 ?>px; color: #333333; font-weight: <?= $fontWeight ?>;" >
                <?= $block->parent()->textWithTemplate($block->text(), $templateData ?? []) ?>
            </<?= $level ?>>
        </td>
    </tr>
</table>
