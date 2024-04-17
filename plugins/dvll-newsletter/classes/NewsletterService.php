<?php
namespace dvll\Newsletter\Classes;

use dvll\Newsletter\PageModels\NewsletterPage;
use Kirby\Data\Data;
use Kirby\Exception\Exception;
use Kirby\Exception\NotFoundException;
use PHPMailer\PHPMailer\PHPMailer;

class NewsletterService
{
    private const EMAIL_FROM = 'vorstand@cvjm-stift-quernheim.de';
    private const EMAIL_FROM_NAME = 'CVJM Stift Quernheim e.V.';

    /**
     * Summary of sendSingleMail
     * @param NewsletterPage $pageModel
     * @param string $to
     * @param string $subject
     * @param string $firstName
     * @param string $name
     * @param array<mixed> $attachments
     * @param string $trackingUrl
     * @throws \Kirby\Exception\NotFoundException
     * @return array{email: string, status: string, statusicon: string, info: string, name: string}
     */
    public static function sendSingleMail($pageModel, string $to, string $subject, string $firstName, string $name, array $attachments = [], $trackingUrl = null): array
    {

        $from = new \Kirby\Cms\User([
            'email' => self::EMAIL_FROM,
            'name' => self::EMAIL_FROM_NAME,
        ]);

        try {
            kirby()->email([
                'from' => $from,
                'replyTo' => $from,
                'to' => $to,
                'subject' => $subject,
                'template' => 'newsletter-mail',
                'data' => [
                    'isEmail' => true,
                    'page' => $pageModel,
                    'to' => $to,
                    'recipientTemplateData' => $pageModel->templateData($firstName, $name, $to),
                    'trackingUrl' => $trackingUrl,
                ],
                'attachments' => $attachments,
                'beforeSend' =>
                /** @param PHPMailer $mailer */
                function ($mailer) {
                    /** @var \Kirby\Filesystem\File $image */
                    $image = asset(NewsletterPage::LOGO_PATH);
                    $mailer->AddEmbeddedImage($image->root(), 'logo', $image->filename(), PHPMailer::ENCODING_BASE64, $image->mime());

                    return $mailer;
                }
            ]);

            $result = [
                'email' => $to,
                'name' => $firstName . ' ' . $name,
                'status' => 'sent',
                'statusicon' => '✔️',
                'info' => (new \DateTime())->format('d.m.Y H:i:s'),
            ];
        } catch (NotFoundException $e) {
            # Throw new exception if template parts are not found and mail sending should be interrupted
            throw new NotFoundException($e->getMessage());
        } catch (\Exception $e) {
            $result = [
                'email' => $to,
                'name' => $firstName . ' ' . $name,
                'status' => 'error',
                'statusicon' => '❌',
                'info' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * @param NewsletterPage $model
     * @param bool $test
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{id?: number, categories?: string, email: string, firstname: string, name: string}[]
     */
    private static function getRecipients($model, bool $test = false, string|null $email = null) {
        $recipients = [];

        if ($test) {
            $recipients = $model->testRecipientsArray();
        } else {
            $recipients = $model->getRecipients($email);
        }


        if (count($recipients) == 0) {
            throw new Exception([
                'key' => 'dvll.newsletterNoRecipients',
                'fallback' => 'Keine validen ' . ($test ? 'Testempfänger' : 'Empfänger') . ' gefunden.',
                'httpCode' => 400,
            ]);
        }

        return $recipients;
    }

    /**
     * @param NewsletterPage $model
     * @param bool $test
     * @param string|null $email
     * @throws \Kirby\Exception\Exception
     * @return array{successfulDelivery: int, errorDelivery: int, message: string, results: mixed}
     */
    public static function sendNewsletter($model, bool $test = false, string|null $email = null)
    {
        NewsletterPage::validateNewsletterFields($model);

        $recipients = self::getRecipients($model, $test, $email);

        $log = '';

        $subject = $test ? '[Test] ' . $model->content->get('subject') : $model->content->get('subject');

        $newResults = [];

        try {
            foreach ($recipients as $recipient) {
                $to = $recipient['email'];
                $firstName = $recipient['firstname'];
                $name = $recipient['name'];
                $trackingUrl = $model->trackingUrl(bin2hex(random_bytes(8)), $test);

                $newResults[] = self::sendSingleMail(
                    pageModel: $model,
                    to: $to,
                    subject: $subject,
                    firstName: $firstName,
                    name: $name,
                    attachments: $model->files()->data(),
                    trackingUrl: $trackingUrl
                );
            }
        } catch (\Exception $e) {
            $log = $e->getMessage();

            $model->update([
                'log' => $log,
                'results' => Data::encode($model->getMergedResults($newResults), 'yaml'),
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
            $model->update([
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

        if (!$test) {
            if (!$model->isSent()) {
                $model = $model->setSent();
            }

            $model = $model
                ->update(
                    [
                        'log' => $log,
                        'results' => Data::encode(
                        $model->getMergedResults($newResults), 'yaml'),
                    ]
                );
        }

        return [
            'successfulDelivery' => count($resultsSuccessful),
            'errorDelivery' => count($resultsWithError),
            'message' => $log,
            'results' => $newResults,
        ];;
    }

    /**
     * @param NewsletterPage $model
     * @param bool $test
     * @return array{id: number, categories: string, email: string, firstname: string, name: string}[]
     */
    public static function checkSend($model, $test = false)
    {
        NewsletterPage::validateNewsletterFields($model);

        return self::getRecipients($model, $test);
    }

}
