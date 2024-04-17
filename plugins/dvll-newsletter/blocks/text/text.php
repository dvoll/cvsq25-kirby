<?php
/** @var \Kirby\Cms\Block $block */
/** @var dvll\Newsletter\PageModels\NewsletterPage $parent */

$parent = $block->parent();
?>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; padding-bottom: 10px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
            <?= $parent->textWithTemplate($block->text(), $templateData ?? []) ?>
        </td>
    </tr>
</table>
