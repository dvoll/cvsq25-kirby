<?php

use Kirby\Cms\App;

$base = dirname(__DIR__);

require $base . '/kirby/bootstrap.php';
require $base . '/vendor/autoload.php';

$kirby = new App([
	'roots' => [
		'index'    => __DIR__,
		'base'     => $base,
        'content'  => $base . '/content',
        'site'     => $base . '/site',
        'storage'  => $storage = $base . '/storage',
        'accounts' => $storage . '/accounts',
		'license'  => $storage . '/.license',
        'cache'    => $storage . '/cache',
        'sessions' => $storage . '/sessions',
	]
]);

echo $kirby->render();
