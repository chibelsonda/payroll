<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvitationException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends BaseApiController
{
    public function __construct(
        protected InvitationService $invitationService
    ) {}

    /**
     * Get all invitations for the active company
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = app('active_company_id');

        $invitations = $this->invitationService->getCompanyInvitations($companyId);

        return $this->successResponse(
            InvitationResource::collection($invitations),
            'Invitations retrieved successfully'
        );
    }

    /**
     * Create a new invitation
     */
    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $user = $request->user();
        $companyId = app('active_company_id');
        $validated = $request->validated();

        try {
            $invitation = $this->invitationService->createInvitation(
                $companyId,
                $user,
                $validated['email'],
                $validated['role']
            );

            return $this->createdResponse(
                new InvitationResource($invitation),
                'Invitation sent successfully'
            );
        } catch (InvitationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                [],
                [],
                422
            );
        }
    }

    /**
     * Get invitation details by token (public endpoint)
     */
    public function showByToken(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return $this->errorResponse('Token is required', [], [], 400);
        }

        $invitation = Invitation::where('token', $token)
            ->with(['company', 'inviter'])
            ->first();

        if (!$invitation) {
            return $this->errorResponse('Invitation not found', [], [], 404);
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid', [], [], 422);
        }

        if ($invitation->isExpired()) {
            return $this->errorResponse('This invitation has expired', [], [], 422);
        }

        return $this->successResponse(
            new InvitationResource($invitation),
            'Invitation retrieved successfully'
        );
    }

    /**
     * Accept an invitation
     */
    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $invitation = $this->invitationService->acceptInvitation(
                $validated['token'],
                $user
            );

            // CRITICAL: Re-authenticate user using web guard and regenerate session
            // This ensures the session cookie is properly set and maintained
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $request->session()->save();

            // Refresh the user model to get updated relationships
            $user->refresh();

            return $this->successResponse(
                new InvitationResource($invitation),
                'Invitation accepted successfully'
            );
        } catch (InvitationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                [],
                [],
                422
            );
        }
    }

    /**
     * Cancel an invitation
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->invitationService->cancelInvitation($invitation);

        return $this->successResponse(null, 'Invitation cancelled successfully');
    }
}
