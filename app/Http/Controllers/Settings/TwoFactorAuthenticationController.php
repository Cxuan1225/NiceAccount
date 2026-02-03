<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class TwoFactorAuthenticationController extends Controller {
    public function __construct() {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $this->middleware('password.confirm')->only('show');
        }
    }

    public function show(TwoFactorAuthenticationRequest $request) : Response {
        $request->ensureStateIsValid();

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        return Inertia::render('settings/TwoFactor', [
            'twoFactorEnabled'     => $user->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
