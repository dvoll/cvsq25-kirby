<?php
/** @var \Kirby\Cms\Block $block */
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="padding: 5px 20px 10px 20px;">
            <!-- Button : BEGIN -->
            <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: auto;">
                <tr>
                    <td class="button-td button-td-primary" style="border-radius: 4px; background: #d2182e;">
                        <a class="button-a button-a-primary" href="<?= $block->url()->toUrl() ?>"
                            style="background: #d2182e; border: 1px solid #d2182e; font-family: sans-serif; font-size: 15px; line-height: 15px; text-decoration: none; padding: 13px 17px; color: #ffffff; display: block; border-radius: 4px;"><?= $block->label()->html() ?></a>
                    </td>
                </tr>
            </table>
            <!-- Button : END -->
        </td>
    </tr>
</table>
