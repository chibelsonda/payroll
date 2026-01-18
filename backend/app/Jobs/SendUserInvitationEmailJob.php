<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\UserInvitationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendUserInvitationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $invitedEmail,
        public readonly string $inviterName,
        public readonly string $companyName,
        public readonly string $inviteLink,
        public readonly Carbon $expiresAt
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->invitedEmail)
                ->send(new UserInvitationMail(
                    invitedEmail: $this->invitedEmail,
                    inviterName: $this->inviterName,
                    companyName: $this->companyName,
                    inviteLink: $this->inviteLink,
                    expiresAt: $this->expiresAt
                ));
        } catch (\Exception $e) {
            Log::error('Failed to send invitation email', [
                'email' => $this->invitedEmail,
                'company' => $this->companyName,
                'invite_link' => $this->inviteLink,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendUserInvitationEmailJob failed after all retries', [
            'email' => $this->invitedEmail,
            'company' => $this->companyName,
            'invite_link' => $this->inviteLink,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
