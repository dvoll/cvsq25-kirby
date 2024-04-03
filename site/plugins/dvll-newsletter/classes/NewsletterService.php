<?php
namespace dvll\Newsletter\Classes;

use dvll\Newsletter\PageModels\NewsletterPage;
use Kirby\Exception\NotFoundException;
use PHPMailer\PHPMailer\PHPMailer;

class NewsletterService
{

    /**
     * Summary of sendSingleMail
     * @param NewsletterPage $pageModel
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
    public static function sendSingleMail($pageModel, $from, string $to, string $subject, $message, string $firstName, string $name, array $attachments = [], $trackingUrl = null): array
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
                    $image = asset('assets/newsletter/newsletter-logo.png');
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

}
