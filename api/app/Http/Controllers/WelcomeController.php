<?php

namespace App\Http\Controllers;

use App\Models\AppReleaseSetting;
use Illuminate\Contracts\View\View;

class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $settings = AppReleaseSetting::current();

        return view('welcome', [
            'settings' => $settings,
            'webAppUrl' => $settings->web_app_url,
            'androidApkUrl' => $settings->androidApkUrl(),
        ]);
    }
}
