<?php

use Kirby\Cms\App;
use Kirby\Data\Yaml;
use Kirby\Filesystem\F;

App::plugin('dvll/sitepackage', [
	'blueprints' => [
		'programmatic/admin-tools' => function (App $kirby) {
			if (($user = $kirby->user()) && $user->isAdmin()) {
				return Yaml::decode(F::read(dirname(__DIR__, 2) . '/blueprints/tabs/admin-tools.yml'));
			}
		},
	],
]);
