<?php
namespace dvll\Newsletter\PageModels;

use dvll\Newsletter\Classes\NewsletterService;
use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use Kirby\Data\Data;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class NewsletterPage extends Page
{

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
    public function templateData($firstName = null, $name = '', $email = 'mail@exmaple.com') {

        if ($firstName == null && $userName = $this->getUserFirstName()) {
            $firstName = $userName;
        }

        return [
            'vorname' => $firstName ?? 'Du',
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
        $allSubscriber = $subscriberField->__call('yaml');
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
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{successfulDelivery: int, errorDelivery: int, message: string, results: mixed}
     */
    public function sendNewsletter(bool $test = false, string|null $email = null)
    {
        $this->validateNewsletterFields();

        $recipients = $this->getRecipientsOrError($test, $email);

        $log = '';

        $subject = $test ? '[Test] ' . $this->content->get('subject') : $this->content->get('subject');

        $newResults = [];

        try {
            foreach ($recipients as $recipient) {
                $to = $recipient['email'];
                $firstName = $recipient['firstname'];
                $name = $recipient['name'];
                $trackingUrl = $this->trackingUrl(bin2hex(random_bytes(8)), $test);

                $newResults[] = NewsletterService::sendSingleMail(
                    pageModel: $this,
                    to: $to,
                    subject: $subject,
                    firstName: $firstName,
                    name: $name,
                    attachments: $this->files()->data(),
                    trackingUrl: $trackingUrl
                );
            }
        } catch (\Exception $e) {
            $log = $e->getMessage();

            $this->update([
                'log' => $log,
                'results' => Data::encode($newResults, 'yaml'),
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

        $resultsSuccessful = array_filter($newResults, function ($result) {
            return $result['status'] === 'sent';
        });
        $resultsWithError = array_filter($newResults, function ($result) {
            return $result['status'] === 'error';
        });

        if (count($resultsSuccessful) <= 0) {
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

        $returnValue = [
            'successfulDelivery' => count($resultsSuccessful),
            'errorDelivery' => count($resultsWithError),
            'message' => $log,
            'results' => $newResults,
        ];

        if ($test) {
            return $returnValue;
        }

        $results = [];
        $page = $this;
        if ($this->intendedTemplate() == 'newsletter-sent') {
            /** @var array{name: string, email: string, status: string, info: string}[] */
            $existingResults = $this->content()->get('results')->__call('yaml');

            // add items from newResults to existingResults or replace if email is the same
            foreach ($existingResults as $existingResult) {
                $newResult = array_filter($newResults, function ($newResult) use ($existingResult) {
                    return $newResult['email'] == $existingResult['email'];
                });

                $newResult = array_values($newResult);

                if (empty($newResult)) {
                    $results[] = $existingResult;
                } else {
                    $results[] = $newResult[0];
                }
            }
        } else {
            $page = $page->convertTo('newsletter-sent');
            $page = $page->changeStatus('listed');
            $results = $newResults;
        }

        $page = $page->update([
            'log' => $log,
            'results' => Data::encode($results, 'yaml'),
        ]);

        return $returnValue;
    }

    /**
     * @throws \Kirby\Exception\Exception
     */
    public function validateNewsletterFields(): void
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
     * @return array{id: number, categories: string, email: string, firstname: string, name: string}[]
     */
    public function checkSend($test = false) {
        $this->validateNewsletterFields();

        return $this->getRecipientsOrError($test);
    }

    /**
     * @param bool $test
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{id?: number, categories?: string, email: string, firstname: string, name: string}[]
     */
    public function getRecipientsOrError(bool $test = false, string|null $email = null) {
        $recipients = $this->getRecipients($test, $email);

        if (count($recipients) == 0) {
            throw new Exception([
                'key' => 'dvll.newsletterNoRecipients',
                'fallback' => 'Keine validen Empfänger gefunden.',
                'httpCode' => 400,
            ]);
        }

        return $recipients;
    }

    /**
     * @param bool $test
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{id?: number, categories?: string, email: string, firstname: string, name: string}[]
     */
    private function getRecipients(bool $test = false, string|null $email = null)
    {
        if ($test) {
            if (($testRecipientsString = $this->content()->get('testRecipients')) != '') {
                $recipients = array_map(function ($testEmail) {
                    return [
                        'email' => $testEmail,
                        'firstname' => $this->getUserFirstName() ?? 'Du',
                        'name' => 'Test-Nachname'
                    ];
                }, explode(',', $testRecipientsString));
            } else {
                $recipients = [];
            }
            return $recipients;
        }

        $recipients = $this->getSubscribers(
            explode(',', $this->content()->get('audience'))
        );

        if ($this->intendedTemplate() == 'newsletter-sent') {
            /** @var array{name: string, email: string, status: string, info: string}[] */
            $results = $this->content()->get('results')->__call('yaml');
            $resultsWithErrors = A::filter($results, function ($result) {
                return A::get($result, 'status') == 'error';
            });
            $recipients = A::filter($recipients, function ($recipient) use ($resultsWithErrors) {
                return A::filter($resultsWithErrors, function ($result) use ($recipient) {
                    return A::get($result, 'email') ==  A::get($recipient, 'email');
                });
            });
        }

        if (!$email) {
            return $recipients;
        }

        $recipientWithEmail = A::filter($recipients, function ($recipient) use ($email) {
            return $recipient['email'] == $email;
        });

        // Reset array keys
        $recipientWithEmail = array_values($recipientWithEmail);

        // Check if recipient was found
        if (empty($recipientWithEmail)) {
            return [];
        }

        // Get the first (and only) recipient
        $recipients = [];
        $recipients[] = $recipientWithEmail[0];
        return $recipients;
    }

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
     * @return string|null
     */
    private function getUserFirstName() {
        $user = kirby()->user();
        if ($user) {
            $userName = $user->name()->value();
            return explode(' ', $userName)[0];
        }
        return null;
    }

}
