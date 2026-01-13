<?php

namespace App\Services;

use App\Exceptions\InvitationException;
use App\Jobs\SendUserInvitationEmailJob;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvitationService
{
    /**
     * Create a new invitation
     *
     * @param int $companyId The company ID to invite the user to
     * @param User $inviter The user sending the invitation
     * @param string $email The email address to invite
     * @param string $role The role to assign (default: 'employee')
     * @return Invitation The created invitation
     */
    public function createInvitation(int $companyId, User $inviter, string $email, string $role = 'employee'): Invitation
    {
        $company = Company::findOrFail($companyId);

        return $this->createInvitationForCompany($company, $inviter, $email, $role);
    }

    /**
     * Create a new invitation for a company
     *
     * @param Company $company The company to invite the user to
     * @param User $inviter The user sending the invitation
     * @param string $email The email address to invite
     * @param string $role The role to assign (default: 'employee')
     * @return Invitation The created invitation
     */
    public function createInvitationForCompany(Company $company, User $inviter, string $email, string $role = 'employee'): Invitation
    {
        // Check if user already belongs to this company
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->companies()->where('companies.id', $company->id)->exists()) {
            throw new InvitationException('This user is already a member of this company.');
        }

        // Check if there's a pending invitation for this email and company
        $existingInvitation = Invitation::where('company_id', $company->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            // Cancel the old invitation and create a new one
            $existingInvitation->update([
                'status' => 'cancelled'
            ]);
        }

        // Create invitation
        $invitation = Invitation::create([
            'company_id' => $company->id,
            'inviter_id' => $inviter->id,
            'email' => $email,
            'role' => $role,
            'token' => Invitation::generateToken(),
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7), // Expires in 7 days
        ]);

        // Dispatch email job to queue
        $this->sendInvitationEmail($invitation, $inviter, $company);

        return $invitation;
    }

    /**
     * Accept an invitation
     *
     * @param string $token The invitation token
     * @param User $user The user accepting the invitation
     * @return Invitation The accepted invitation
     */
    public function acceptInvitation(string $token, User $user): Invitation
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            throw new \Exception('Invitation not found or already used');
        }

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            throw new \Exception('Invitation has expired');
        }

        if ($invitation->email !== $user->email) {
            throw new InvitationException(
                "This invitation is for {$invitation->email}, but you're logged in as {$user->email}. " .
                "Please log out and log in with the email address the invitation was sent to."
            );
        }

        return DB::transaction(function () use ($invitation, $user) {
            // Attach user to company
            $user->companies()->syncWithoutDetaching([$invitation->company_id]);

            // Assign role to user for this company
            $this->assignRoleToUser($user, $invitation->company, $invitation->role);

            // Mark invitation as accepted
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return $invitation;
        });
    }

    /**
     * Cancel an invitation
     *
     * @param Invitation $invitation The invitation to cancel
     * @return bool
     */
    public function cancelInvitation(Invitation $invitation): bool
    {
        if ($invitation->status !== 'pending') {
            throw new \Exception('Only pending invitations can be cancelled');
        }

        return $invitation->update(['status' => 'cancelled']);
    }

    /**
     * Get all invitations for a company by company ID
     *
     * @param int $companyId The company ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyInvitations(int $companyId)
    {
        $company = Company::findOrFail($companyId);

        return Invitation::where('company_id', $company->id)
            ->with(['inviter', 'company'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all invitations for a company
     *
     * @param Company $company The company
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyInvitationsByCompany(Company $company)
    {
        return Invitation::where('company_id', $company->id)
            ->with(['inviter', 'company'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Send invitation email via queue
     *
     * @param Invitation $invitation The invitation
     * @param User $inviter The user who sent the invitation
     * @param Company $company The company
     * @return void
     */
    protected function sendInvitationEmail(Invitation $invitation, User $inviter, Company $company): void
    {
        try {
            $inviterName = trim("{$inviter->first_name} {$inviter->last_name}") ?: $inviter->email;
            $inviteLink = $this->generateInvitationLink($invitation->token);
            $companyName = $company->name ?: config('invitation.email.company_name');

            SendUserInvitationEmailJob::dispatch(
                invitedEmail: $invitation->email,
                inviterName: $inviterName,
                companyName: $companyName,
                inviteLink: $inviteLink,
                expiresAt: $invitation->expires_at
            )->onQueue('emails');
        } catch (\Exception $e) {
            Log::error('Failed to dispatch invitation email job', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a signed, expiring invitation link
     *
     * @param string $token The invitation token
     * @return string The invitation URL
     */
    protected function generateInvitationLink(string $token): string
    {
        $baseUrl = config('invitation.email.accept_url');

        return "{$baseUrl}?token={$token}";
    }

    /**
     * Assign role to user for a company
     *
     * @param User $user The user
     * @param Company $company The company
     * @param string $role The role name
     * @return void
     */
    protected function assignRoleToUser(User $user, Company $company, string $role): void
    {
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($company->id);
            $registrar->forgetCachedPermissions();

            // Remove any existing roles for this company
            DB::table('model_has_roles as mhr')
                ->where('mhr.model_id', $user->id)
                ->where('mhr.model_type', 'App\Models\User')
                ->where('mhr.company_id', $company->id)
                ->delete();

            // Assign the new role (map 'employee' to 'user' for Spatie)
            $spatieRole = $role === 'employee' ? 'user' : $role;
            $user->assignRole($spatieRole);
            $registrar->forgetCachedPermissions();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
