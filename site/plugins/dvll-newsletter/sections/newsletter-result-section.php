<?php

/** @var Kirby\Cms\Section $this */

return [
    'props' => [
        'id' => function () {
            /** @var dvll\Newsletter\PageModels\NewsletterPage $nPage */
            $nPage = $this->model();
            return $nPage->id();
        },
        'reports' => function () {
            /** @var dvll\Newsletter\PageModels\NewsletterPage $nPage */
            $nPage = $this->model();
            return $nPage->content()->get('results')->__call('yaml');
        },
    ]
];
