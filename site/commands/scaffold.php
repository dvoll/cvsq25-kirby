<?php

use Kirby\CLI\CLI;
use Kirby\Cms\Page;
use Kirby\Filesystem\F;

return [
    'description' => 'Scaffold kirby',
    'args' => [],
    'command' => static function (CLI $cli): void {
        kirby()->impersonate('kirby');
        $root = kirby()->roots()->base();
        $cli->info('Scaffolding kirby-baukasten...');

        if (!F::exists($root . '/.env')) {
            $cli->info('Copying .env file...');
            F::copy($root . '/.env.example', $root . '/.env');
        }

        if (!page('home')) {
            $cli->info('Creating empty home page...');
            $page = Page::create([
                'slug' => 'home',
                'template' => 'home',
                'content' => [],
            ]);
            $page->changeStatus('listed');
        }

        if (!page('error')) {
            $cli->info('Creating empty error page...');
            $page = Page::create([
                'slug' => 'error',
                'template' => 'error',
                'content' => [],
            ]);
            $page->changeStatus('listed');
        }

        if (!page('page://images')) {
            $cli->info('Creating images page...');
            $page = Page::create([
                'slug' => 'images',
                'template' => 'images',
                'content' => [
                    'uuid' => 'images',
                ],
            ]);
            $page->changeStatus('unlisted');
        }

        $cli->info('Scaffolding done!');
    }
];
