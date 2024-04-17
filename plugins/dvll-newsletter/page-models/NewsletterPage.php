<?php
namespace dvll\Newsletter\PageModels;

use Error;
use Kirby\Cms\Page;
use Kirby\Exception\Exception;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class NewsletterPage extends Page
{
    public const TEMPLATE_NAME = 'newsletter';
    public const TEMPLATE_NAME_SENT = 'newsletter-sent';
    public const LOGO_PATH = 'assets/newsletter/newsletter-logo.png';

    /**
     * @param string $uid
     * @return self
     * @throws Error
     */
    public static function getPageWithUid(string $uid): self {
        /** @var self $page */
        $page = kirby()->page('newsletters/' . $uid);
        if ($page == null) {
            throw new Error('Seite nicht gefunden', 404);
        }
        if ($page->intendedTemplate() != NewsletterPage::TEMPLATE_NAME && $page->intendedTemplate() != NewsletterPage::TEMPLATE_NAME_SENT) {
            throw new Error('Fehlerhafte anfrage', 400);
        }
        return $page;
    }

    /**
     * @throws \Kirby\Exception\Exception
     */
    public static function validateNewsletterFields(self $model): void
    {
        $errors = $model->errors();

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

    #[\Override]
    public function isCacheable(): bool {
        return false;
    }

    /**
     * Summary of logoSrc
     * @param bool $isEmail
     * @return string|null
     */
    public function logoSrc($isEmail = false) {
        if (!$isEmail) {
            /** @var \Kirby\Filesystem\File $image */
            $image = asset(self::LOGO_PATH);
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
     *
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
    private function getSubscribers($categories)
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
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{id?: number, categories?: string, email: string, firstname: string, name: string}[]
     */
    public function getRecipients(string|null $email = null)
    {

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
     * @return array{email: string, firstname: string, name: string}[]
     */
    public function testRecipientsArray() {
        if (($testRecipientsString = $this->content()->get('testRecipients')) != '') {
            return array_map(function ($testEmail) {
                return [
                    'email' => $testEmail,
                    'firstname' => $this->getUserFirstName() ?? 'Du',
                    'name' => 'Test-Nachname'
                ];
            }, explode(',', $testRecipientsString));
        } else {
            return [];
        }
    }

    /**
     * Summary of trackingUrl
     * @param string $cid
     * @param bool $test
     * @return string
     */
    public function trackingUrl($cid = null, $test = false): string
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

    public function setSent(): self {
        return $this->convertTo('newsletter-sent')->changeStatus('listed');
    }

    public function isSent(): bool {
        return $this->intendedTemplate() == 'newsletter-sent';
    }

    /**
     * @param array{name: string, email: string, status: string, info: string}[] $newResults
     * @return array{name: string, email: string, status: string, info: string}[]
     */
    public function getMergedResults($newResults) {
        $existingReports = $this->content()->get('results')->__call('yaml');

        foreach ($newResults as $newResult) {
            $found = false;
            foreach ($existingReports as $key => $existingReport) {
                if ($existingReport['email'] == $newResult['email']) {
                    $existingReports[$key] = $newResult;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existingReports[] = $newResult;
            }
        }
        return $existingReports;
    }

}
