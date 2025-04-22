<?php

return [
    'general' => [
        'success' => 'Operation successful',
        'created' => 'Resource created successfully',
        'no_content' => 'No content available',
        'unauthorized' => 'Unauthorized',
        'forbidden' => 'Forbidden',
        'not_found' => 'Resource not found',
        'validation_failed' => 'Validation failed',
        'server_error' => 'Internal server error',
        'custom_error' => 'Something went wrong',
    ],
    'auth' => [
        'success' => [
            'login' => 'User logged in successfully.',
            'register' => 'User registered successfully. Verification email sent.',
            'logout' => 'User logged out successfully.',
            'password_reset' => 'Password has been reset successfully.',
            'email_verification_already' => 'Email already verified.',
            'account_verification_email_sent' => 'Verification email sent.',
            'email_verified' => 'Email successfully verified.'
        ],
        'failed' => [
            'login' => 'Invalid credentials.',
            'email_verification' => 'Invalid verification link.'
        ]
    ],
    'product' => [
        'success' => [
            'create' => 'Product created successfully.'

        ],
        'failed' => [
            'create' => 'Error creating product: :message',
            'invalid_address' => 'The address you specified does not belong to you'
        ]
    ]
];
