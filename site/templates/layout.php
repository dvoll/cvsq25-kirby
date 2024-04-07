<?php

/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Page $page
 * @var Kirby\Cms\Site $site
 */

snippet('layout', slots: true); ?>
<?= $page->content()->get('blocks')->__call('toBlocks') ?>
<?php endsnippet() ?>
