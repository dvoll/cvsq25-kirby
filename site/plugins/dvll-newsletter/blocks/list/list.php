<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; padding-bottom: 10px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
            <?php /** @var \Kirby\Cms\Block $block */ ?>
            <?= $block->parent()->textWithTemplate($block->text(), $templateData ?? []) ?>
        </td>
    </tr>
</table>
