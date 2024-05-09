<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::min(6)->letters()->symbols()->numbers()->mixedCase(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()
            ->with('title', trans('public.success_update'))
            ->with('success', trans('public.successfully_update_password'));
    }

    /**
     * Create or Update the user's pin.
     */
    public function user_pin(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'current_pin' => ['sometimes', 'required'],
            'pin' => ['required', 'confirmed'],
        ])->setAttributeNames([
            'current_pin' => trans('public.current_pin'),
            'pin' => trans('public.new_pin'),
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $user = $request->user();

            if (!is_null($user->security_pin) && !Hash::check($request->get('current_pin'), $user->security_pin)) {
                throw ValidationException::withMessages(['current_pin' => trans('public.current_pin_invalid')]);
            }

            $user->update([
                'security_pin' => Hash::make($request->pin),
            ]);

            return back()
                ->with('title', trans('public.success_update'))
                ->with('success', trans('public.successfully_update_pin'));
        }

    }
}
