<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\SendOtp;
use App\Models\Country;
use App\Models\User;
use App\Models\VerifyOtp;
use App\Notifications\OtpNotification;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $countries = Country::all();
        $formattedCountries = $countries->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->name,
            ];
        });

        return Inertia::render('Auth/Register', [
            'countries' => $formattedCountries,
        ]);
    }

    public function firstStep(Request $request)
    {
        $rules = [
            'country' => 'required',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        $attributes = [
            'country' => trans('public.Country'),
            'email' => trans('public.Email'),
            'phone' => trans('public.Mobile Phone'),
            'password' => trans('public.Password'),
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($request->form_step == 1) {
            $validator->validate();
        } elseif ($request->form_step == 2) {
            $additionalRules = [
                'name' => 'required|regex:/^[a-zA-Z0-9\p{Han}. ]+$/u|max:255',
                'chinese_name' => 'nullable|regex:/^[a-zA-Z0-9\p{Han}. ]+$/u',
                'dob' => 'required',
                'country' => 'required'
            ];
            $rules = array_merge($rules, $additionalRules);

            $additionalAttributes = [
                'name'=> trans('public.Name'),
                'chinese_name' => trans('public.Chinese Name'),
                'dob' => trans('public.Date Of Birth'),
                'country' => trans('public.Country'),
            ];
            $attributes = array_merge($attributes, $additionalAttributes);

            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attributes);
            $validator->validate();
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
        $otp = VerifyOtp::where('email', $request->email)->first();

        $expirationTime = Carbon::parse($otp->updated_at)->addMinutes(5);

        if (Carbon::now()->greaterThan($expirationTime)) {
            throw ValidationException::withMessages([
                'verification_code' => 'The Verification Code expired.'
            ]);
        }

        if($otp->otp != $request->verification_code){
            throw ValidationException::withMessages(['verification_code' => 'Invalid Verification Code']);
        }

        $phone = $request->dial_code . $request->phone;
        $userData = [
            'name' => $request->name,
            'chinese_name' => $request->chinese_name,
            'email' => $request->email,
            'country' => $request->country,
            'phone' => $phone,
            'dob' => $request->dob,
            'password' => Hash::make($request->password),
            'role' => 'member',
        ];

        if ($request->has('referral_code')) {
            $referral_code = $request->input('referral_code');
            $check_referral_code = User::where('referral_code', $referral_code)->first();

            if ($check_referral_code) {
                $upline_id = $check_referral_code->id;

                $hierarchyList = empty($check_referral_code['hierarchyList']) ? "-" . $upline_id . "-" : $check_referral_code['hierarchyList'] . $upline_id . "-";

                $userData['upline_id'] = $upline_id;
                $userData['hierarchyList'] = $hierarchyList;
            }
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
        event(new Registered($user));

        return redirect()->route('login')->with('toast', 'Successfully Created Account');
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

        return back()->with('toast', 'Successfully Sent OTP');
    }
}
