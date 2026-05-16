<?php

declare(strict_types=1);

return [
    'footer' => [
        'rights' => 'All rights reserved.',
        'website' => 'qr-master.app',
    ],

    'verify_email' => [
        'subject' => 'Verify your email address',
        'greeting' => 'Hello!',
        'intro' => 'Please click the button below to verify your email address and activate your account.',
        'action' => 'Verify Email Address',
        'outro' => 'If you did not create an account, no further action is required.',
        'subcopy' => 'If you\'re having trouble clicking the button, copy and paste the URL below into your browser:',
        'expiry' => 'This verification link will expire in :count minutes.',
    ],

    'reset_password' => [
        'subject' => 'Reset Password',
        'greeting' => 'Hello!',
        'intro' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'expiry' => 'This password reset link will expire in :count minutes.',
        'outro' => 'If you did not request a password reset, no further action is required.',
        'subcopy' => 'If you\'re having trouble clicking the button, copy and paste the URL below into your browser:',
    ],

    'invitation' => [
        'subject' => 'You\'ve been invited to :team',
        'greeting' => 'Hello!',
        'intro' => ':inviter has invited you to join the ":team" workspace as :role.',
        'action' => 'Accept Invitation',
        'expiry' => 'This invitation link will expire in 7 days.',
        'outro' => 'If you did not expect this invitation, you can ignore this email.',
    ],
];
