<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $settingCountries = Country::all();

        // Map the data to match the structure of the countries array
        $formattedCountries = $settingCountries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
                'phone_code' => $country->phone_code,
            ];
        });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'countries' => $formattedCountries,
            'frontIdentityImg' => Auth::user()->getFirstMediaUrl('front_identity'),
            'backIdentityImg' => Auth::user()->getFirstMediaUrl('back_identity'),
            'profileImg' => Auth::user()->getFirstMediaUrl('profile_photo'),
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

        // $this->processImage($request);

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
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

        return Redirect::to('/');
    }

    // protected function processImage(Request $request): void
    // {
    //     $user = $request->user();
    //     if ($image = $request->get('proof_front')) {
    //         $path = storage_path('/app/public/' . $image);
    //         if (file_exists($path)) {
    //             $request->user()->clearMediaCollection('front_identity');
    //             $request->user()->addMedia($path)->toMediaCollection('front_identity');
    //             $user->update([
    //                 'kyc_approval' => 'pending'
    //             ]);
    //         }
    //     }

    //     if ($image_back = $request->get('proof_back')) {
    //         $path = storage_path('/app/public/' . $image_back);
    //         if (file_exists($path)) {
    //             $request->user()->clearMediaCollection('back_identity');
    //             $request->user()->addMedia($path)->toMediaCollection('back_identity');
    //             $user->update([
    //                 'kyc_approval' => 'pending'
    //             ]);
    //         }
    //     }

    //     if ($profile_photo = $request->get('profile_photo')) {
    //         $path = storage_path('/app/public/' . $profile_photo);
    //         if (file_exists($path)) {
    //             $request->user()->clearMediaCollection('profile_photo');
    //             $request->user()->addMedia($path)->toMediaCollection('profile_photo');
    //         }
    //     }
    // }
}
