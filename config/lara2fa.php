<?php

use MustafaAwami\Lara2fa\Features;

return[

    /*
    |--------------------------------------------------------------------------
    | Lara2fa Stack
    |--------------------------------------------------------------------------
    |
    | This configuration value informs Lara2fa which "stack" you will be
    | using for your application. In general, this value is set for you
    | during installation and will not need to be changed after that.
    |
    */
    'stack' => 'react',

    /*
    |--------------------------------------------------------------------------
    | Redirect Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths where the user will be redirected after certain 
    | actions, such as successfully completing two-factor login.
    |
    */
    'redirects' => [
        'two-factor-login' => '/dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lara2fa Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Lara2fa will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Lara2fa routes will be available.
    |
    */

    'prefix' => null,

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Lara2fa defines separate rate limiters for two-factor related
    | actions (email notifications, two-factor code login attempts and
    | passkey logins). Configure those limiters in your application's
    | RateLimiter definitions (in App\Providers\Lara2faServiceProvider).
    |
    */

    'limiters' => [
        // Rate limiter for sending two-factor authentication email notifications.
        'two-factor-email-notify' => 'two-factor-email-notify',
        // Rate limiter for the two-factor authentication login attempts.
        'two-factor-login' => 'two-factor-login',
        // Rate limiter for login attempts using passkeys.
        'passkey-login' => 'passkey-login'
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of Lara2FA's features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features or you can even remove all of these if you need to.
    |
    */
    'features' => [
        Features::authenticatorAppTwoFactorAuthentication([
            // Enable or disable the use of an Authenticator App (like Google Authenticator) for 2FA.
            'enable' => false,
            // Require a second confirmation step after initial setup.
            'confirm' => true,
            // Require the user to confirm their password before enabling or changing this feature.
            'confirmPassword' => true,
            // The time step window (in minutes) to allow for drift when verifying the TOTP code.
            'window' => 1,
            // The length of the secret key generated for the authenticator app.
            'secret-length' => 16
        ]),
        Features::emailTwoFactorAuthentication([
            // Enable or disable the use of email-based codes for 2FA.
            'enable' => false,
            // Require a second confirmation step after initial setup.
            'confirm' => true,
            // Require the user to confirm their password before enabling or changing this feature.
            'confirmPassword' => true,
            // The time (in minutes) for which the email verification code is valid.
            'window' => 10,
        ]),
        Features::passkeys([
            // Enable or disable the use of Passkeys for authentication.
            'enable' => false,

            /*
            |--------------------------------------------------------------------------
            | Max Passkeys
            |--------------------------------------------------------------------------
            |
            | This option is used to define the maximum number of Passkeys a user can have.
            |
            */
            'max_passkeys' => 3,

            /*
            |--------------------------------------------------------------------------
            | Password Confirmation
            |--------------------------------------------------------------------------
            |
            | Here you may specify whether or not password confirmation is 
            | required to enable, create ,update and delete Passkeys.
            |
            */
            'confirmPassword' => true,
            /*
            |--------------------------------------------------------------------------
            | Authentication Mode
            |--------------------------------------------------------------------------
            | 
            | This will determin how users can use thier registerd passkeys. When
            | this is set to Single Factor Authentication (sfa), Users will be able to
            | login with thier passkeys directly without the need to provide thier
            | password. When this is set to Two-Factor Authentication (2fa), then passkey
            | will act as an additional Two-Factor option.
            |
            | The "both" option will allow the users to use thier passkeys both
            | as "sfa" and "2fa".
            | 
            | Supported: "sfa", "2fa", "both".
            |
            */

            'authentication_mode' => 'both',

            /*
            |--------------------------------------------------------------------------
            | Passkey challenge length
            |--------------------------------------------------------------------------
            |
            | Length of the random string used in the challenge request.
            |
            */

            'challenge_length' => 32,

            /*
            |--------------------------------------------------------------------------
            | Passkey timeout (milliseconds)
            |--------------------------------------------------------------------------
            |
            | Time that the caller is willing to wait for the call to complete.
            | See https://webauthn-doc.spomky-labs.com/symfony-bundle/configuration-references#timeout
            |
            */

            'timeout' => 60000,

            /*
            |--------------------------------------------------------------------------
            | Passkey icon
            |--------------------------------------------------------------------------
            |
            | Url which resolves to an image associated with the entity.
            | See https://www.w3.org/TR/webauthn/#dom-publickeycredentialentity-icon
            |
            */

            'icon' => env('PASSKEY_ICON'),

            /*
            |--------------------------------------------------------------------------
            | Passkey Attestation Conveyance
            |--------------------------------------------------------------------------
            |
            | This parameter specify the preference regarding the attestation conveyance
            | during credential generation.
            | See https://www.w3.org/TR/webauthn/#enum-attestation-convey
            |
            | Supported: "none", "indirect", "direct", "enterprise".
            */

            'attestation_conveyance' => 'none',

            /*
            |--------------------------------------------------------------------------
            | Credentials Attachment.
            |--------------------------------------------------------------------------
            |
            | Authentication can be tied to the current device (like when using Windows
            | Hello or Touch ID) or a cross-platform device (like USB Key). When this
            | is "null" the user will decide where to store his authentication info.
            |
            | See https://www.w3.org/TR/webauthn/#enum-attachment
            |
            | Supported: "null", "cross-platform", "platform".
            |
            */

            'attachment_mode' => null,

            /*
            |--------------------------------------------------------------------------
            | User presence and verification
            |--------------------------------------------------------------------------
            |
            | Most authenticators and smartphones will ask the user to actively verify
            | themselves for log in. Use "required" to always ask verify, "preferred"
            | to ask when possible, and "discouraged" to just ask for user presence.
            |
            | See https://www.w3.org/TR/webauthn/#enum-userVerificationRequirement
            |
            | Supported: "required", "preferred", "discouraged".
            |
            */

            'user_verification' => 'preferred',

            /*
            |--------------------------------------------------------------------------
            | Userless (One touch, Typeless) login
            |--------------------------------------------------------------------------
            |
            | By default, users must input their email to receive a list of credentials
            | ID to use for authentication, but they can also login without specifying
            | one if the device can remember them, allowing for true one-touch login.
            |
            | If required or preferred, login verification will be always required.
            |
            | See https://www.w3.org/TR/webauthn/#enum-residentKeyRequirement
            |
            | Supported: "null", "required", "preferred", "discouraged".
            |
            */

            'resident_key' => "preferred",
        ]),
        Features::recoveryCodes([
            // Enable or disable the use of recovery codes for emergency login.
            'enable' => true,
            // Require the user to confirm their password before generating new codes.
            'confirmPassword' => true,
            // Require a Two-Factor Authentication method to be enabled before allowing recovery code generation.
            'requireTwoFactorAuthenticationEnabled' => true,
            // The total number of unique recovery codes that will be generated for the user.
            'numberOfCodesGenerated' => 8
        ]),
    ],

];