<?php
namespace dvll\Newsletter\PageModels;

use dvll\Newsletter\Classes\NewsletterService;
use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use Kirby\Data\Data;
use Kirby\Toolkit\Str;

class NewsletterPage extends Page
{
    private const EMAIL_FROM = 'vorstand@cvjm-stift-quernheim.de';
    private const EMAIL_FROM_NAME = 'CVJM Stift Quernheim e.V.';

    /**
     * Summary of trackingUrl
     * @param string $cid
     * @param bool $test
     * @return string
     */
    private function trackingUrl($cid = null, $test = false): string
    {
        $pageName = urlencode($test ? "https://cvjm-stift-quernheim.de/email-opened-testmail/" : "https://cvjm-stift-quernheim.de/email-opened/");
        $newsletterName = urlencode($this->slug());
        $websiteId = "1";
        $campaignName = urlencode($test ? "emailing-newsletter-test" : "emailing-newsletter");
        $src = "https://statistik.cvsq.de/matomo.php?idsite={$websiteId}&rec=1&bots=1&url={$pageName}{$newsletterName}&action_name=Email%20opened&mtm_campaign={$campaignName}&mtm_keyword={$newsletterName}";

        if ($cid) {
            $src .= "&cid={$cid}";
        }

        return $src;
    }

    /**
     * Summary of logoSrc
     * @param bool $isEmail
     * @return string|null
     */
    public function logoSrc($isEmail = false) {
        if (!$isEmail) {
            /** @var \Kirby\Filesystem\File $image */
            $image = asset('assets/newsletter/newsletter-logo.png');
            return $image->url();
        }
        return 'cid:logo';
    }

    /**
     * @param string $text
     * @param array<mixed> $templateData
     * @return string
     */
    public function textWithTemplate(string $text, $templateData) {
        return Str::template($text, $templateData);
    }

    /**
     * Summary of defaultTemplateData
     * @param string $firstName
     * @param string $name
     * @param string $email
     * @return array<string, string>
     */
    public function templateData($firstName = 'Du', $name = '', $email = 'mail@exmaple.com') {
        return [
            'vorname' => $firstName,
            'nachname' => $name,
            'email' => $email,
        ];
    }

    /**
     * @param string[] $categories
     * @return array{id: int, categories: string, email: string, firstname: string, name: string}[]
     */
    protected function getSubscribers($categories)
    {
        $filteredList = [];
        /** @var \Kirby\Content\Field $subscriberField */
        $subscriberField = $this->parent()->content()->get('subscribers');
        // @phpstan-ignore-next-line
        $allSubscriber = $subscriberField->yaml();
        foreach ($allSubscriber as $item) {
            $itemCategories = $item['categories'];
            $itemCategoryIds = array_map('trim', explode(',', $itemCategories));

            if (array_intersect($itemCategoryIds, $categories)) {
                $filteredList[] = $item;
            }
        }
        return $filteredList;
    }


    /**
     * @param bool $test
     * @throws \Kirby\Exception\Exception
     * @return array{successfulDelivery: int, errorDelivery: int, message: string}
     */
    public function sendNewsletter(bool $test = false)
    {

        $recipients = $this->getRecipients($test);

        $from = new \Kirby\Cms\User([
            'email' => self::EMAIL_FROM,
            'name' => self::EMAIL_FROM_NAME,
        ]);

        $log = '';

        // @phpstan-ignore-next-line
        $message = $this->content()->get('message')->toBlocks();
        $subject = $test ? '[Test] ' . $this->content->get('subject') : $this->content->get('subject');

        $results = [];

        try {
            foreach ($recipients as $recipient) {
                $to = $recipient['email'];
                $firstName = $recipient['firstname'];
                $name = $recipient['name'];
                $trackingUrl = $this->trackingUrl(bin2hex(random_bytes(8)), $test);

                $results[] = NewsletterService::sendSingleMail($this, $from, $to, $subject, $message, $firstName, $name, [], $trackingUrl);
            }
        } catch (\Exception $e) {
            $log = $e->getMessage();
            $this->update([
                'log' => $log,
                'results' => Data::encode($results, 'yaml'),
            ]);
            throw new Exception([
                'key' => 'dvll.newsletterSendFailure',
                'fallback' => 'Fehler beim Versenden des Newsletters',
                'httpCode' => 500,
                'details' => [
                    $e->getMessage()
                ]
            ]);
        }

        $resultsSuccessful = array_filter($results, function ($result) {
            return $result['status'] === 'sent';
        });
        $resultsWithError = array_filter($results, function ($result) {
            return $result['status'] === 'error';
        });

        if (!$test && count($resultsSuccessful) > 0) {
            // Update page with log and results
            $page = $this->convertTo('newsletter-sent');
            $page = $page->changeStatus('listed');
            $page = $page->update([
                'log' => $log,
                'results' => Data::encode($results, 'yaml'),
            ]);
        }
        if (count($resultsSuccessful) < 0) {
            $this->update([
                'log' => $log,
            ]);
            throw new Exception([
                'key' => 'dvll.newsletterSendFailure',
                'fallback' => 'Fehler beim Versenden des Newsletters',
                'httpCode' => 500,
                'details' => array_map(function ($result) {
                    return [
                        'label' => $result['email'],
                        'message' => $result['info']
                    ];
                }, $resultsWithError)
            ]);
        }

        return [
            'successfulDelivery' => count($resultsSuccessful),
            'errorDelivery' => count($resultsWithError),
            'message' => $log
        ];
    }

    public function sendSingle($email) {
        $this->validateNewsletterFields();

        $recipients = $this->getSubscribers(
            explode(',', $this->content()->get('audience'))
        );

        $recipient = array_filter($recipients, function($recipient) use ($email) {
            return $recipient['email'] == $email;
        });

        // Reset array keys
        $recipient = array_values($recipient);

        // Check if recipient was found
        if (empty($recipient)) {
            throw new Exception([
                'key' => 'dvll.newsletterRecipientNotFound',
                'httpCode' => 400,
                'fallback' => 'Der Empfänger scheint nicht Teil des aktuellen newsletters zu sein.'
            ]);
        }

        // Get the first (and only) recipient
        $recipient = $recipient[0];

        $from = new \Kirby\Cms\User([
            'email' => self::EMAIL_FROM,
            'name' => self::EMAIL_FROM_NAME,
        ]);

        $log = '';

        // @phpstan-ignore-next-line
        $message = $this->content()->get('message')->toBlocks();
        $subject = $this->content->get('subject');

        $to = $recipient['email'] ;
        $firstName = $recipient['firstname'];
        $name = $recipient['name'];
        $trackingUrl = $this->trackingUrl(bin2hex(random_bytes(8)));

        $results = $this->content()->get('results')->yaml();

        try {
            $newResult = NewsletterService::sendSingleMail($this, $from, $to, $subject, $message, $firstName, $name, [], $trackingUrl);
        } catch (\Exception $e) {
            $log = $e->getMessage();
            $this->update([
                'log' => $log,
            ]);
            throw new Exception([
                'key' => 'dvll.newsletterSendFailure',
                'fallback' => 'Fehler beim Versenden der Nachricht',
                'httpCode' => 500,
                'details' => [
                    $e->getMessage()
                ]
            ]);
        }

        // update single entry of array $results with email = $to
        $results = array_map(function ($result) use ($to, $newResult) {
            if ($result['email'] == $to) {
                return $newResult;
            }
            return $result;
        }, $results);

        $this->update([
            'log' => $log,
            'results' => Data::encode($results, 'yaml'),
        ]);

        if ($newResult['status'] == 'error') {
            throw new Exception([
                'key' => 'dvll.newsletterSendFailure',
                'fallback' => 'Fehler beim Versenden der Nachricht',
                'httpCode' => 500,
                'details' => [
                    [
                        'label' => $newResult['email'],
                        'message' => $newResult['info']
                    ]
                ]
            ]);
        }

        return $newResult;
    }

    public function validateNewsletterFields()
    {
        $errors = $this->errors();

        // handle required fields
        if (sizeOf($errors) > 0) {
            throw new Exception([
                'key' => 'dvll.newsletterFieldsValidation',
                'httpCode' => 400,
                'details' => $errors,
                'fallback' => 'Der Newsletter ist nicht vollständig und kann daher nicht versendet werden'
            ]);
        }
    }

    /**
     * @param bool $test
     * @throws \Kirby\Exception\Exception
     * @return array{id: number, categories: string, email: string, firstname: string, name: string}
     */
    public function getRecipients(bool $test = false)
    {
        $this->validateNewsletterFields();

        if ($test) {
            if (($testRecipientsString = $this->content()->get('testRecipients')) != '') {
                $recipients = array_map(function ($email) {
                    return [
                        'email' => $email,
                        'firstname' => 'Test-Vorname',
                        'name' => 'Test-Nachname'
                    ];
                }, explode(',', $testRecipientsString));
            } else {
                $recipients = [];
            }
        } else {
            $recipients = $this->getSubscribers(
                explode(',', $this->content()->get('audience'))
            );
        }

        if (count($recipients) === 0) {
            throw new Exception([
                'key' => 'dvll.newsletterNoRecipients',
                'fallback' => $test ? 'Keine Test-Empfänger eingetragen' : 'Keine Empfänger gefunden',
                'httpCode' => 400,
            ]);
        }

        return $recipients;
    }

}
