<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Upload avatar for a user
     *
     * @param User $user The user to upload avatar for
     * @param UploadedFile $file The uploaded file
     * @return User The updated user instance
     */
    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store the new avatar
        $path = $file->store('avatars', 'public');

        // Update user's avatar path
        $user->update(['avatar' => $path]);

        // Refresh user to get updated data
        return $user->fresh();
    }
}
