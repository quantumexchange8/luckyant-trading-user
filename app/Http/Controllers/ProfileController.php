<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use App\Models\PaymentAccount;
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
        $formattedNationalities = Country::all()->map(function ($country) {
            return [
                'value' => $country->nationality,
                'label' => $country->nationality,
            ];
        });

        $formattedCountries = Country::all()->map(function ($country) {
            return [
                'value' => $country->name,
                'label' => $country->name,
                'currency' => $country->currency,
            ];
        });

        $formattedCurrencies = Country::whereIn('id', [132, 233])->get()->map(function ($country) {
            return [
                'value' => $country->currency,
                'label' => $country->currency_name . ' (' . $country->currency . ')',
            ];
        });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'nationalities' => $formattedNationalities,
            'frontIdentityImg' => Auth::user()->getFirstMediaUrl('front_identity'),
            'backIdentityImg' => Auth::user()->getFirstMediaUrl('back_identity'),
            'profileImg' => Auth::user()->getFirstMediaUrl('profile_photo'),
            'paymentAccounts' => PaymentAccount::where('user_id', Auth::id())->latest()->get(),
            'countries' => $formattedCountries,
            'currencies' => $formattedCurrencies,
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
            ->where('role', 'member')
            ->where('status', 'Active')
            ->get();

        foreach ($users as $user_phone) {
            if ($user_phone->phone == $phone_number) {
                throw ValidationException::withMessages(['phone' => trans('public.invalid_mobile_phone')]);
            }
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'dial_code' => $dial_code,
            'phone' => $phone_number,
            'nationality' => $request->nationality,
            'gender' => $request->gender,
            'address_1' => $request->address,
            'identification_number' => $request->identification_number,
            'kyc_approval' => 'Unverified',
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
