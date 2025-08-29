<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\VerifyOtp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\SelectOptionService;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\RegisterRequest;
use App\Notifications\OtpNotification;
use App\Services\RunningNumberService;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Notifications\NewUserWelcomeNotification;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create($referral = null): Response
    {
        abort(404);
        return Inertia::render('Auth/Register', [
            'countries' => (new SelectOptionService())->getCountries(),
            'nationality' => (new SelectOptionService())->getNationalities(),
            'referral_code' => $referral,
        ]);
    }

    public function firstStep(Request $request)
    {
        $rules = [
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'username' => 'required|string|max:255|unique:' . User::class,
            'phone' => 'required',
            'password' => ['required', 'confirmed', Password::min(6)->letters()->symbols()->numbers()->mixedCase()],
        ];

        $attributes = [
            'email' => trans('public.email'),
            'username' => trans('public.username'),
            'phone' => trans('public.mobile_phone'),
            'password' => trans('public.password'),
        ];

        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone = '+' . $phone;
        }

        // Merge the modified phone number back into the request
        $request->merge(['phone' => $phone]);

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($request->form_step == 1) {
            $validator->validate();
        } elseif ($request->form_step == 2) {
            // Validation rules for step 2
            $additionalRules = [
                'name' => 'required|regex:/^[\p{L}\p{N}\p{M}. @]+$/u|max:255',
                'chinese_name' => 'nullable|regex:/^[\p{L}\p{N}\p{M}. @]+$/u',
                'dob_year' => 'required|numeric|digits:4|min:1900|max:'.date('Y'),
                'dob_month' => 'required|numeric|min:1',
                'dob_day' => 'required|numeric|min:1',
                'country' => 'required',
                'nationality' => 'required',
            ];

            // Merge additional rules with existing rules
            $rules = array_merge($rules, $additionalRules);

            // Set additional attributes
            $additionalAttributes = [
                'name'=> trans('public.name'),
                'chinese_name' => trans('public.chinese_name'),
                'dob_year' => trans('public.year'),
                'dob_month' => trans('public.month'),
                'dob_day' => trans('public.day'),
                'country' => trans('public.country'),
                'nationality' => trans('public.nationality'),
            ];

            // Merge additional attributes with existing attributes
            $attributes = array_merge($attributes, $additionalAttributes);

            // Create a new validator with updated rules and attributes
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attributes);

            // Validate the request
            $validator->validate();

            if($request->dob_year && $request->dob_month && $request->dob_day){
                $dob = date($request->dob_year . '-' . $request->dob_month . '-' . $request->dob_day);
                if ($dob && !checkdate($request->dob_month, $request->dob_day, $request->dob_year)) {
                    throw ValidationException::withMessages(['dob' => trans('public.invalid_date')]);
                }
            }
        }

        return to_route('register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
//        $otp = VerifyOtp::where('email', $request->email)->first();
//
//        $expirationTime = Carbon::parse($otp->updated_at)->addMinutes(5);
//
//        if (Carbon::now()->greaterThan($expirationTime)) {
//            throw ValidationException::withMessages([
//                'verification_code' => 'The Verification Code expired.'
//            ]);
//        }
//
//        if($otp->otp != $request->verification_code){
//            throw ValidationException::withMessages(['verification_code' => 'Invalid Verification Code']);
//        }

        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone = '+' . $phone;
        }

        $dob = $request->dob_year . '-' . $request->dob_month . '-' . $request->dob_day;

        $userData = [
            'name' => $request->name,
            'chinese_name' => $request->chinese_name,
            'email' => $request->email,
            'username' => $request->username,
            'country' => $request->country,
            'nationality' => $request->nationality,
            'dial_code' => $request->dial_code,
            'phone' => $phone,
            'dob' => $dob,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'top_leader_id' => null,
        ];

        if ($request->referral_code) {
            $referral_code = $request->input('referral_code');
            $check_referral_code = User::where('referral_code', $referral_code)->first();

            if ($check_referral_code) {
                $upline_id = $check_referral_code->id;
                $top_leader_id = $check_referral_code->top_leader_id ? $check_referral_code->top_leader_id : $check_referral_code->id;
                $hierarchyList = empty($check_referral_code['hierarchyList']) ? "-" . $upline_id . "-" : $check_referral_code['hierarchyList'] . $upline_id . "-";

                $userData['top_leader_id'] = $top_leader_id;
                $userData['upline_id'] = $upline_id;
                $userData['hierarchyList'] = $hierarchyList;
                $userData['is_public'] = $check_referral_code->is_public;

                if ($userData['top_leader_id'] != 7) {
                    $userData['rank_up_status'] = 'manual';
                }
            }
        } else {
            $userData['top_leader_id'] = 7;
            $userData['upline_id'] = 7;
            $userData['hierarchyList'] = '-7-';
        }

        $user = User::create($userData);

        $front_identity = $request->front_identity;
        $back_identity = $request->back_identity;

        if ($request->hasFile('front_identity')) {
            $user->addMedia($front_identity)->toMediaCollection('front_identity');
        }

        if ($request->hasFile('back_identity')) {
            $user->addMedia($back_identity)->toMediaCollection('back_identity');
        }

        $user->setReferralId();

        $leader = $user->getFirstLeader();

        if ($leader) {
            $user->enable_bank_withdrawal = $leader->enable_bank_withdrawal;
            $user->save();
        }

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'Cash Wallet',
            'type' => 'cash_wallet',
            'wallet_address' => RunningNumberService::getID('cash_wallet'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'Bonus Wallet',
            'type' => 'bonus_wallet',
            'wallet_address' => RunningNumberService::getID('bonus_wallet'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'name' => 'E-Wallet',
            'type' => 'e_wallet',
            'wallet_address' => RunningNumberService::getID('e_wallet'),
        ]);
        event(new Registered($user));

        if (App::environment('production')) {
            Notification::route('mail', $user->email)
                ->notify(new NewUserWelcomeNotification($user));
        }

        return redirect()->route('login')
            ->with('title', trans('public.success_registration'))
            ->with('success', trans('public.successfully_registration'));
    }

    public function sendOtp(Request $request)
    {
        $otp = $request->input('otp');
        $email = $request->input('email');

        // Save the OTP and email using the VerifyOtp model
        $verfiy_otp = VerifyOtp::updateOrCreate([
            'email' => $email,
        ], [
            'otp' => $otp,
        ]);

        Notification::route('mail', $email)
            ->notify(new OtpNotification($verfiy_otp->otp));

        return back()->with('toast', trans('public.success_sent_otp'));
    }

    public function getAllCountries(Request $request)
    {
        $locale = app()->getLocale();

        $countries = Country::query()
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('translations', 'like', "%{$search}%");
                });
            })
            ->select('id', 'name', 'translations')
            ->get()
            ->map(function ($country) use ($locale) {
                $translations = json_decode($country->translations, true);
                $label = $translations[$locale] ?? $country->name;
                return [
                    'id' => $country->id,
                    'name' => $label,
                ];
            });

        return response()->json($countries);
    }
}
