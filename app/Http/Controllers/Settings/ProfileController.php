<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Upload or update the authenticated user's avatar image.
     */
    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => [
                'required',
                File::image()->max(5 * 1024), // 5MB
            ],
        ]);

        $user = $request->user();

        // Replace existing avatar with the new one (singleFile collection ensures replacement)
        $media = $user
            ->addMediaFromRequest('avatar')
            ->usingFileName(sprintf('user-%d-avatar.%s', $user->id, $request->file('avatar')->getClientOriginalExtension()))
            ->toMediaCollection('avatar');

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'avatar' => $user->getFirstMediaUrl('avatar', 'thumb'),
            'avatar_original' => $media->getUrl(),
        ]);
    }
}
