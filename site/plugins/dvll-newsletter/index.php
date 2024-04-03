<?php
@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use Kirby\Cms\Page;
use dvll\Newsletter\PageModels\NewsletterPage;
use dvll\Newsletter\PageModels\NewslettersPage;

App::plugin('dvll/newsletter', [
    'sections' => [
        'newsletter-action' => require __DIR__ . '/sections/newsletter-action-section.php',
    ],
    'blueprints' => [
        'pages/newsletters' => __DIR__ . '/blueprints/pages/newsletters.yml',
        'sections/newsletters' => __DIR__ . '/blueprints/sections/newsletters.yml',
        # single newsletter
        'pages/newsletter' => __DIR__ . '/blueprints/pages/newsletter.yml',
        'pages/newsletter-sent' => __DIR__ . '/blueprints/pages/newsletter-sent.yml',
        'layouts/newsletter' => __DIR__ . '/blueprints/layouts/newsletter.yml',
        # fields
        'fields/audience' => __DIR__ . '/blueprints/fields/audience.yml',
        'fields/message' => __DIR__ . '/blueprints/fields/message.yml',
        'fields/results' => __DIR__ . '/blueprints/fields/results.yml',
        'fields/subject' => __DIR__ . '/blueprints/fields/subject.yml',
        # blocks
        'blocks/mail-heading' => __DIR__ . '/blocks/heading/heading.yml',
        'blocks/mail-text' => __DIR__ . '/blocks/text/text.yml',
        'blocks/mail-image' => __DIR__ . '/blocks/image/image.yml',
        'blocks/mail-images' => __DIR__ . '/blocks/images/images.yml',
        'blocks/mail-button' => __DIR__ . '/blocks/button/button.yml',
        'blocks/mail-list' => __DIR__ . '/blocks/list/list.yml',
        'blocks/mail-line' => __DIR__ . '/blocks/line/line.yml',
    ],
    'snippets' => [
        'blocks/mail-heading' => __DIR__ . '/blocks/heading/heading.php',
        'blocks/mail-text' => __DIR__ . '/blocks/text/text.php',
        'blocks/mail-image' => __DIR__ . '/blocks/image/image.php',
        'blocks/mail-images' => __DIR__ . '/blocks/images/images.php',
        'blocks/mail-button' => __DIR__ . '/blocks/button/button.php',
        'blocks/mail-list' => __DIR__ . '/blocks/list/list.php',
        'blocks/mail-line' => __DIR__ . '/blocks/line/line.php',
    ],
    'templates' => [
        'newsletter' => __DIR__ . '/templates/newsletter-mail.html.php',
        // 'newsletters' => __DIR__ . '/templates/newsletter-mail.html.php',
        // 'newsletter-sent' => __DIR__ . '/templates/newsletter.php',
        # mail
        'emails/newsletter-mail.html' => __DIR__ . '/templates/newsletter-mail.html.php',
    ],
    'pageModels' => [
        'newsletter' => NewsletterPage::class,
        'newsletter-sent' => NewsletterPage::class,
        'newsletters' => NewslettersPage::class,
    ],
    'hooks' => [
        'page.duplicate:after' => function (Page $duplicatePage, Page $originalPage) {
            if ($duplicatePage->intendedTemplate() == 'newsletter-sent') {
                $duplicatePage->changeTemplate('newsletter');
                $duplicatePage->update([
                    'results' => '',
                    'log' => '',
                ]);
            }
        },
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => ['newsletters/(:any)/send', 'newsletters/(:any)/send/(:num)'],
                'method' => 'GET',
                'action' => function (string $uid, int $test = 0) {
                    /** @var dvll\Newsletter\PageModels\NewsletterPage $page */
                    $page = kirby()->page('newsletters/' . $uid);
                    if ($page == null) {
                        throw new Error('page not found', 404);
                    }
                    $deliveryData = $page->sendNewsletter(boolval($test));
                    return [
                        'success' => true,
                        'message' => $test ? 'Test erfolgreich gesendet' : 'Newsletter erfolgreich gesendet',
                        'data' => $deliveryData
                    ];
                }
            ],
        ]
    ],
    'siteMethods' => [
        'unixTimestamp' => function () {
            return time();
        }
    ],
]);