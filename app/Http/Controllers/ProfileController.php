<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Country;
use App\Models\SettingRank;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectOptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PaymentAccountRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $locale = app()->getLocale();

        $rank = SettingRank::where('id', \Auth::user()->setting_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'nationalities' => (new SelectOptionService())->getNationalities(),
            'frontIdentityImg' => Auth::user()->getFirstMediaUrl('front_identity'),
            'backIdentityImg' => Auth::user()->getFirstMediaUrl('back_identity'),
            'profileImg' => Auth::user()->getFirstMediaUrl('profile_photo'),
            'paymentAccounts' => PaymentAccount::where('user_id', Auth::id())->latest()->get(),
            'countries' => (new SelectOptionService())->getCountries(),
            'currencies' => (new SelectOptionService())->getCurrencies(),
            'rank' => $translations[$locale] ?? $rank->name,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
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
            ->where('status', 'Active')
            ->get();

        foreach ($users as $user_phone) {
            if ($user_phone->phone == $phone_number) {
                throw ValidationException::withMessages(['phone' => trans('public.invalid_mobile_phone')]);
            }
        }

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'dial_code' => $request->dial_code,
            'phone' => $phone_number,
            'country' => $request->country,
            'nationality' => $request->nationality,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'address_1' => $request->address,
            'identification_number' => $request->identification_number,
        ]);

        if ($user->getChanges()) {
            $user->kyc_approval = 'Unverified';
            $user->save();
        }

        if ($request->hasFile('proof_front')) {
            $user->clearMediaCollection('front_identity');
            $user->addMedia($request->proof_front)->toMediaCollection('front_identity');
            $user->kyc_approval = 'Pending';
            $user->save();
        }
        if ($request->hasFile('proof_back')) {
            $user->clearMediaCollection('back_identity');
            $user->addMedia($request->proof_back)->toMediaCollection('back_identity');
            $user->kyc_approval = 'Pending';
            $user->save();
        }
        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        return Redirect::route('profile.edit')->with('title', trans('public.success_update'))->with('success', trans('public.successfully_update'));
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

    public function addPaymentAccount(PaymentAccountRequest $request)
    {
        $user = Auth::user();
        $payment_method = $request->payment_method;

        $data = [
            'user_id' => $user->id,
            'payment_account_name' => $request->payment_account_name,
            'payment_platform' => $request->payment_method,
            'payment_platform_name' => $request->payment_platform_name,
            'account_no' => $request->account_no,
        ];

        if ($payment_method == 'Bank') {
            $data['bank_branch_address'] = $request->bank_branch_address;
            $data['bank_swift_code'] = $request->bank_swift_code;
            $data['bank_code'] = $request->bank_code;
            $data['bank_code_type'] = $request->bank_code_type;
            $data['country'] = $request->country;
            $data['currency'] = $request->currency;
        } elseif ($payment_method == 'Crypto') {
            $data['currency'] = 'USDT';
        }

        PaymentAccount::create($data);

        return back()->with('title', trans('public.success_created_account'))->with('success', trans('public.successfully_created_account'));
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
