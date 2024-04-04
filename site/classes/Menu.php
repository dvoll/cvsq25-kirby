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

// use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Page;

/**
 * Helper class for customizing the panel menu
 *
 * Based on original work by Lukas Kleinschmidt
 * https://gist.github.com/lukaskleinschmidt/247a957ebcde66899757a16fead9a039
 */
class Menu
{
    public static array $pages = [];

    public static string $path;

    public static function path(): string
    {
        return static::$path ??= App::instance()->request()->path()->toString();
    }

    public static function page(string $label = null, string $icon = null, string|Page $link = null, Closure|bool $current = null): array
    {
        if ($link instanceof Page) {
            $page = $link;
            $link = $link->panel()->path();
        }

        if (is_null($link)) {
            return [];
        }

        $data = [
            'label' => $label || !isset($page) ? t($label, $label) : $page->title()->value(),
            'link' => $link,
            'current' => $current ?? fn () =>
            str_contains(static::path(), $link)
        ];

        if ($icon) {
            $data['icon'] = $icon;
        }

        return static::$pages[] = $data;
    }

    public static function site(string $label = null, string $icon = null): array
    {
        $data = [
            'current' => function (string $id = null) {
                if ($id !== 'site') {
                    return false;
                }

                foreach (static::$pages as &$page) {
                    if (str_contains(static::path(), $page['link'])) {
                        return false;
                    }
                }

                return true;
            },
        ];

        if ($label) {
            $data['label'] = t($label, $label);
        }

        if ($icon) {
            $data['icon'] = $icon;
        }

        return $data;
    }
}
