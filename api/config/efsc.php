<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login email domain
    |--------------------------------------------------------------------------
    |
    | Student and parent accounts are created as {local-part}@{domain}.
    | Users may sign in with just the local part (admission no. or CNIC).
    |
    */
    'login_email_domain' => env('EFSC_LOGIN_EMAIL_DOMAIN', 'efsc-ya.com'),

];
