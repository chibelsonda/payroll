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
     * Accept an invitation
     */
    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $invitation = $this->invitationService->acceptInvitation(
            $validated['token'],
            $user
        );

        return $this->successResponse(
            new InvitationResource($invitation),
            'Invitation accepted successfully'
        );
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
