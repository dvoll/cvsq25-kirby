<?php
namespace dvll\Newsletter\PageModels;

use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use Kirby\Exception\NotFoundException;
use Kirby\Data\Data;
use Kirby\Toolkit\Str;
use PHPMailer\PHPMailer\PHPMailer;

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
            $image = asset('assets/newsletter-logo.png');
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
     * @return array{id: number, categories: string, email: string, firstname: string, name: string}[]
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
     * Summary of sendSingleMail
     * @param \Kirby\Cms\User $from
     * @param string $to
     * @param string $subject
     * @param \Kirby\Cms\Blocks $message
     * @param string $firstName
     * @param string $name
     * @param array<mixed> $attachments
     * @param string $trackingUrl
     * @throws \Kirby\Exception\NotFoundException
     * @return array{email: string, status: string, statusIcon: string, info: string}
     */
    protected function sendSingleMail($from, string $to, string $subject, $message, string $firstName, string $name, array $attachments = [], $trackingUrl = null): array
    {

        try {
            kirby()->email([
                'from' => $from,
                'replyTo' => $from,
                'to' => $to,
                'subject' => $subject,
                'template' => 'newsletter-mail',
                'data' => [
                    'isEmail' => true,
                    'page' => $this,
                    'to' => $to,
                    'recipientTemplateData' => $this->templateData($firstName, $name, $to),
                    'trackingUrl' => $trackingUrl,
                ],
                'attachments' => $attachments,
                'beforeSend' => /** @param PHPMailer $mailer */ function ($mailer) {
                    /** @var \Kirby\Filesystem\File $image */
                    $image = asset('assets/newsletter-logo.png');
                    $mailer->AddEmbeddedImage($image->root(), 'logo', $image->filename(), PHPMailer::ENCODING_BASE64, $image->mime());

                    return $mailer;
                }
            ]);

            $result = [
                'email' => $to,
                'status' => 'sent',
                'statusIcon' => '✔️',
                'info' => (new \DateTime())->format('d.m.Y H:i:s'),
            ];
        } catch (NotFoundException $e) {
            # Throw new exception if template parts are not found and mail sending should be interrupted
            throw new NotFoundException($e->getMessage());
        } catch (\Exception $e) {
            $result = [
                'email' => $to,
                'status' => 'error',
                'statusIcon' => '❌',
                'info' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * @param bool $test
     * @throws \Kirby\Exception\Exception
     * @return array{successfulDelivery: int, errorDelivery: int, message: string}
     */
    public function sendNewsletter(bool $test = false)
    {
        $errors = $this->errors();

        // handle required fields
        if (sizeOf($errors) > 0) {
            throw new Exception([
                'key' => 'dvll.newsletterFieldsValidation',
                'httpCode' => 400,
                'details' => $errors,
            ]);
        }

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
                'fallback' => $test ? 'Keine Testempfänger eingetragen' : 'Keine Empfänger gefunden',
                'httpCode' => 400,
            ]);
        }

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

                $results[] = $this->sendSingleMail($from, $to, $subject, $message, $firstName, $name, [], $trackingUrl);
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
        } else {
            $this->update([
                'log' => $log,
            ]);
        }

        return [
            'successfulDelivery' => count($resultsSuccessful),
            'errorDelivery' => count($resultsWithError),
            'message' => $log
        ];
    }

}
