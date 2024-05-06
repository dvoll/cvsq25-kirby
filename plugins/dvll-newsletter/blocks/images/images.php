<?php
/** @var \Kirby\Cms\Block $block */
$alt = $block->alt();
$caption = $block->caption();
/** @var \Kirby\Cms\File[] $images */
$images = $block->images()->toFiles();
$crop = $block->crop()->isTrue();
$ratio = $block->ratio()->or('auto');
$imageMaxWidth = 600 /count($images);
switch ($ratio) {
    case '1/1':
        $imageMaxHeight = $imageMaxWidth;
        break;
    case '4/3':
        $imageMaxHeight = intval($imageMaxWidth / 4 * 3);
        break;
    case '3/4':
        $imageMaxHeight = intval($imageMaxWidth / 3 * 4);
        break;
    case '2/3':
        $imageMaxHeight = intval($imageMaxWidth / 2 * 3);
        break;
    case '3/2':
        $imageMaxHeight = intval($imageMaxWidth / 3 * 2);
        break;
    default:
        $imageMaxHeight = $imageMaxWidth;
}
?>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <?php foreach ($images as $image): ?>
            <td valign="mitddle" width="50%" style="vertical-align: middle; padding-top: 10px;  padding-bottom: 10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td>
                            <?php
                                if ($crop) {
                                    $src = $image->crop($imageMaxWidth, $imageMaxHeight, [
                                        'crop' => 'center',
                                        'quality' => 80,
                                    ])->url();
                                    $width = $imageMaxWidth;
                                    $height = $imageMaxHeight;
                                } else {
                                    $src = $image->resize($imageMaxWidth)->url();
                                    /** @var \Kirby\Image\Image $image */
                                    $dimensions = $image->dimensions()->fitWidthAndHeight($imageMaxWidth, 600);
                                    $width = $dimensions->width();
                                    $height = $dimensions->height();
                                }
                                // @phpstan-ignore-next-line
                                $alt = $alt->or($image->alt());
                            ?>
                            <img src="<?= $src ?>" alt="<?= $alt->esc() ?>" border="0"
                                style="width: 100%; max-width: <?= $imageMaxWidth ?>px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto; display: block;"
                                class="g-img" width="<?= $width; ?>" height="<?= $height ?>">
                        </td>
                    </tr>
                </table>
            </td>
        <?php endforeach ?>
    </tr>
</table>
<?php if ($caption->isNotEmpty()): ?>
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
