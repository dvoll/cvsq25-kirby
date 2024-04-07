<?php

/**
 * MIT License
 *
 * Copyright (c) 2023 Tobias Möritz
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files(the "Software"), to deal
 *     in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and / or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 */

use Kirby\CLI\CLI;
use Kirby\Cms\Page;

return [
    'description' => 'Scaffold kirby',
    'args' => [],
    'command' => static function (CLI $cli): void {
        kirby()->impersonate('kirby');
        // @phpstan-ignore-next-line
        $cli->info('Scaffolding kirby-baukasten...');

        if (!page('home')) {
            // @phpstan-ignore-next-line
            $cli->info('Creating empty home page...');
            $page = Page::create([
                'slug' => 'home',
                'template' => 'home',
                'content' => [],
            ]);
            $page->changeStatus('listed');
        }

        if (!page('error')) {
            // @phpstan-ignore-next-line
            $cli->info('Creating empty error page...');
            $page = Page::create([
                'slug' => 'error',
                'template' => 'error',
                'content' => [],
            ]);
        }

        if (!page('images')) {
            // @phpstan-ignore-next-line
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

        if (!page('newsletters')) {
            // @phpstan-ignore-next-line
            $cli->info('Creating newsletters page...');
            $page = Page::create([
                'slug' => 'newsletters',
                'template' => 'newsletters',
            ]);
            $page->changeStatus('unlisted');
        }

        // @phpstan-ignore-next-line
        $cli->info('Scaffolding done!');
    }
];
