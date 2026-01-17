<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\User;

class EmailVerificationController extends BaseApiController
{
    /**
     * Verify signed email link.
     */
    public function verify(Request $request, $id, $hash): JsonResponse
    {
        // Validate signed URL. Host/scheme must match APP_URL (used when signing).
        if (!URL::hasValidSignature($request)) {
            return $this->errorResponse('Invalid or expired verification link.', [], [], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', [], [], 404);
        }

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return $this->errorResponse('Invalid verification link.', [], [], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse(null, 'Email verified successfully');
    }

    /**
     * Resend verification email for unverified users.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified');
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse(null, 'Verification link sent');
    }
}
