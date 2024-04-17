<?php

/** @var Kirby\Cms\Section $this */

return [
    'props' => [
        'status' => function () {
            /** @var dvll\Newsletter\PageModels\NewsletterPage $nPage */
            $nPage = $this->model();
            return $nPage->status();
        },
        'id' => function () {
            /** @var dvll\Newsletter\PageModels\NewsletterPage $nPage */
            $nPage = $this->model();
            return $nPage->id();
        },
    ]
];
