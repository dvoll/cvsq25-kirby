<?php

/** @var Kirby\Cms\Page $page
 *  @var Kirby\Cms\Site $site
 *  @var Kirby\Cms\App $kirby */

use dvll\Sitepackage\Helper;

if (Helper::getEnv('PAGE_VIEW_LOGIN') && !$kirby->user()) {
    go('/panel');
}
?>

<!DOCTYPE html>
<html lang="<?= $kirby->language()?->code() ?>">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <?php snippet('seo/head') ?>

    <link rel="icon" href="/favicon.svg" />
    <link rel="mask-icon" href="/favicon.svg" color="#000000" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
    <meta name="theme-color" content="#000000">

    <?= vite()->css('src/main.ts') ?>
</head>

<body class="flex flex-col min-h-screen antialiased overflow-x-clip">
    <?php snippet('core/skip-nav') ?>
    <?php snippet('core/nav') ?>
    <main class="container flex-grow">
        <div id="main"></div>
        <?= $slot ?>
    </main>
    <?php snippet('core/footer') ?>
    <?= vite()->js('src/main.ts') ?>
    <?php snippet('seo/schemas') ?>
</body>

</html>
