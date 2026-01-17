<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Override to point verification to the SPA while keeping signed backend URL.
     */
    protected function verificationUrl($notifiable): string
    {
        $expiresAt = now()->addHours(24);

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl) {
            URL::forceRootUrl($appUrl);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if ($scheme) {
                URL::forceScheme($scheme);
            }
        }

        $signedUrl = URL::temporarySignedRoute(
            'v1.verification.verify',
            $expiresAt,
            [
                'uuid' => $notifiable->uuid,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $parsed = parse_url($signedUrl);
        parse_str($parsed['query'] ?? '', $queryParams);

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

        $params = array_merge($queryParams, [
            'uuid' => $notifiable->uuid,
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return $frontendUrl . '/verify-email?' . http_build_query($params);
    }

    /**
     * Build the mail representation.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your email for CyfrinTech')
            ->markdown('emails.verify-email', [
                'url' => $verificationUrl,
            ]);
    }
}
