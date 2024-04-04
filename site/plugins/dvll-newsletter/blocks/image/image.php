<?php

/** @var \Kirby\Cms\Block $block */

use Kirby\Toolkit\Str;

$alt = $block->alt();
$caption = $block->caption();
$crop = $block->crop()->isTrue();
$link = $block->link();
$ratio = $block->ratio()->or('auto');
$src = null;
$width = null;
$height = null;

if ($image = $block->image()->toFile()) {
    $alt = $alt->or($image->alt());
    $src = $image->resize(560)->url();
    $dimensions = $image->dimensions()->fitWidthAndHeight(580, 600);
    $width = $dimensions->width();
    $height = $dimensions->height();
}
?>

<?php if ($src) : ?>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="background-color: #ffffff; padding-left: 20px; padding-right: 20px; padding-top: 10px;  padding-bottom: 10px;" class="darkmode-bg">
                <?php if ($link->isNotEmpty()) : ?>
                    <a href="<?= Str::esc($link->toUrl()) ?>">
                        <img src="<?= $src ?>" alt="<?= $alt->esc() ?>" border="0" style="width: 100%; max-width: 600px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto; display: block;" class="g-img" width="<?= $width; ?>" height="<?= $height ?>">
                    </a>
                <?php else : ?>
                    <img src="<?= $src ?>" alt="<?= $alt->esc() ?>" border="0" style="width: 100%; max-width: 600px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto; display: block;" class="g-img" width="<?= $width; ?>" height="<?= $height ?>">
                <?php endif ?>

        </tr>
    </table>
<?php endif ?>
<?php if ($caption->isNotEmpty()) : ?>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="text-align: center; font-family: sans-serif; line-height: 20px; color: #555555; padding: 0 10px 10px 20px;">
                            <p style="margin: 0; font-size: 12px !important;">
                                <?= $caption ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
<?php endif ?>
