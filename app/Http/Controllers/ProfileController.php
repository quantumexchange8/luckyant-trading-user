<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
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
                'value' => $country->nationality,
                'label' => $country->nationality,
            ];
        });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'nationalities' => $formattedCountries,
            'frontIdentityImg' => Auth::user()->getFirstMediaUrl('front_identity'),
            'backIdentityImg' => Auth::user()->getFirstMediaUrl('back_identity'),
            'profileImg' => Auth::user()->getFirstMediaUrl('profile_photo'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone_number = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone_number = '+' . $phone;
        }

        $users = User::where('dial_code', $request->dial_code)
            ->whereNot('id', $user->id)
            ->where('role', 'member')
            ->where('status', 'Active')
            ->get();

        foreach ($users as $user_phone) {
            if ($user_phone->phone == $phone_number) {
                throw ValidationException::withMessages(['phone' => 'Invalid Mobile Phone']);
            }
        }

        $user->update([
            'name' => $request->name,
            'dial_code' => $dial_code,
            'phone' => $phone_number,
            'nationality' => $request->nationality,
            'gender' => $request->gender,
            'address_1' => $request->address,
            'identification_number' => $request->identification_number,
        ]);

        if ($request->hasFile('proof_front')) {
            $user->clearMediaCollection('front_identity');
            $user->addMedia($request->proof_front)->toMediaCollection('front_identity');
        }
        if ($request->hasFile('proof_back')) {
            $user->clearMediaCollection('back_identity');
            $user->addMedia($request->proof_back)->toMediaCollection('back_identity');
        }
        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        return Redirect::route('profile.edit')->with('title', 'Success update')->with('success', trans('public.Successfully Updated Profile'));
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
