<p align="center"><img width="500" height="185" src="/art/logo.png" alt="Logo Lara2fa"></p>

<p align="center">
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/v/mustafa-awami/lara2fa" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/dt/mustafa-awami/lara2fa" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/l/mustafa-awami/lara2fa" alt="License"></a>
<a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-12%2B-red?logo=laravel" alt="Laravel 12+"></a>
</p>

**Lara2FA** is a modern, flexible, and developer-friendly **Two-Factor Authentication (2FA)** package for Laravel.  
It supports **powerful authentication methods** out of the box:

- ✉️ **Email OTP**
- 🔢 **Authenticator Apps (TOTP)**
- 🪪 **WebAuthn (Passkeys / Security Keys / Biometrics)**
- 🔑 **Recovery Codes**

Designed for simplicity, security, and seamless integration into any Laravel project.

---

# 🚀 Features

- 🔢 Compatible with Google Authenticator, Authy, and 1Password
- ✉️ Built-in Email OTP
- 🪪 WebAuthn support for FIDO2 devices, Windows Hello, Touch ID, and Passkeys
- 🧩 Easy install command with feature selection
- 🔒 Secure and standards-compliant implementation
- 🧑‍💻 Developer Friendly – clean, easy setup, and customizable flows

---

# 📝 Prerequisites

* **Laravel Framework:** 12.37.0+
* **Frontend Stack:** The installer currently supports **Inertia** with **React** or **Vue**, **Livewire** can be added if requested.

---

# 🧰 Installation

Install via Composer:

```bash
composer require mustafa-awami/lara2fa -W
```

--- 

# ⚙️ Set Up

## Step 1️⃣

After installing via composer, publish resources using the `lara2fa:install` Artisan command

```bash
php artisan lara2fa:install
```

During installation, you’ll be asked which starter kit/stack you are currently using: 

```
 Which stack are you using?
  [1] react
  [2] vue
```
- **Note:** Currently supported stacks are React and Vue only. Livewire can be added if requested.

Then you will be asked which of the following 2FA methods would you like to enable:

```
 Which 2FA methods would you like to enable? (comma separated):
  [1] Authenticator App (TOTP)
  [2] Email OTP
  [3] Passkeys
```

Depending on the selected methods, the published `lara2fa.php` config file will be updated to enable the selected methods and disable the rest.

### ⚠️ Warning

This instalation process may override some of your files in your project directory, see the full list of files at the [Troubleshooting Section](#-troubleshooting--important-notes)

## Step 2️⃣

In `User.php` model, replace:

```php
use Laravel\Fortify\TwoFactorAuthenticatable;
```

with:

```php
use MustafaAwami\Lara2fa\Traits\TwoFactorAuthenticatable;
```

## Step 3️⃣

In `settings.php` route file, replace:

```php
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
```

with:

```php
use MustafaAwami\Lara2fa\Http\Controllers\Settings\TwoFactorAuthenticationController;
```

## Step 4️⃣

In `fortify.php` config file, disable the two-factor feature by commenting it out like so:

```php
// Features::twoFactorAuthentication([
//     'confirm' => true,
//     'confirmPassword' => true,
//     // 'window' => 0,
// ]),
```

## Step 5️⃣

Run the migrations to create the new tables:
```bash
php artisan migrate
```
Finally, run the build command:
```bash
npm run build
```
--- 

# 🧪 Example Repository

You will find an example of usage on [Mustafa-Awami/lara2fa-example](https://github.com/Mustafa-Awami/lara2fa-example).

--- 

# 💻 Usage

Users can enable any of the two-factor methods in thier settings page.

![two-factor-settings](/art/two-factor-settings.png)

By enabling any of the main 3 methods, recovery codes will be enabled automaticly.

Users who have enabled any of the two-factor methods will be automatically redirected to the two-factor-challenge page after entering their password.

![two-factor-challenge](/art/two-factor-challenge.png)

If a user enabled passkeys, they can login directly without entering their password in the login page by clicking on the `Use Passkey` button (passwordless authentication).

![login](/art/login.png)

---

# 🛠️ Configuration (Optional)

## Two-Factor Authentication 

### 1- Authenticator App (TOTP)

This section configures Two-Factor Authentication (2FA) using a time-based one-time password (TOTP) app like Google Authenticator or Authy.

You will find the configuration for this feature in `lara2fa.php` config file under `Features:authenticatorAppTwoFactorAuthentication([...])`.

- `'enable' => true`:  Enable or disable this feature.
- `'confirm' => true`: Requires users to confirm setup by entering a code from their app.
- `'confirmPassword' => true`: Requires users to re-enter their password before enabling or modifying this feature.
- `'window' => 1`: Allows a 1-minute grace period for time differences (clock drift) between the user's device and the server.
- `'secret-length' => 16`: The length of the secret key generated for the authenticator app.

### 2- Email (OTP)

This configures 2FA by sending a one-time code to the user's registered email address.

You will find the configuration for this feature in `lara2fa.php` config file under `Features:emailTwoFactorAuthentication([...])`.

- `'enable' => true`: Enable or disable this feature.
- `'confirm' => true`: Requires a confirmation step during setup.
- `'confirmPassword' => true`: Requires users to re-enter their password before enabling or modifying this feature.
- `'window' => 10`: The time (in minutes) for which the email verification code is valid.

### 3- Passkeys

This configures login using device-based security like Face ID, Touch ID, Windows Hello, or physical security keys (YubiKey). It can also be used as passwordless authentication.

You will find the configuration for this feature in `lara2fa.php` config file under `Features:passkeys([...])`.

- `'enable' => true`: Enable or disable this feature.
- `'max_passkeys' => 3`: The maximum number of Passkeys a user can have.
- `'confirmPassword' => true`: Requires users to re-enter their password to add or remove passkeys.
- `'authentication_mode' => 'both'`: Allows passkeys to be used for both single-factor (passwordless) and two-factor (password + passkey) authentication.
- `'challenge_length' => 32`: The length of the random string used in the authentication challenge.
- `'timeout' => 60000`: Time (in ms) that the caller is willing to wait for the call to complete.
- `'icon' => env('PASSKEY_ICON')`: URL which resolves to an image associated with the entity.
- `'attestation_conveyance' => 'none'`: This parameter specify the preference regarding the attestation conveyance during credential generation.
- `'attachment_mode' => null`: Authentication can be tied to the current device or a cross-platform device or buth.
- `'user_verification' => 'preferred'`: Most authenticators and smartphones will ask the user to actively verify themselves for log in. Use "required" to always ask verify, "preferred" to ask when possible, and "discouraged" to just ask for user presence.
- `'resident_key' => 'preferred'`: By default, users must input their email to receive a list of credentials ID to use for authentication, but they can also login without specifying one if the device can remember them, allowing for true one-touch login. If required or preferred, login verification will be always required.

### 4- Recovery Codes

This configures one-time-use backup codes for users who lose access to their 2FA device.

You will find the configuration for this feature in `lara2fa.php` config file under `Features:recoveryCodes([...])`.

- `'enable' => true`: Enable or disable this feature.
- `'confirmPassword' => true`: Requires users to re-enter their password to view or generate new codes.
- `'requireTwoFactorAuthenticationEnabled' => true`: Require a Two-Factor Authentication method to be enabled before allowing recovery code generation.
- `'numberOfCodesGenerated' => 8`: The total number of unique recovery codes that will be generated for the user.

## Routes

If you want to customize the `lara2fa.php` route file, first publish it with the following command:

```bash
php artisan vendor:publish --tag=lara2fa-routes
```

Then, in `routes/web.php`, add the following line at the end:

```php
require __DIR__.'/lara2fa.php';
```

Make sure to disable the original route file by adding `Lara2fa::ignoreRoutes();` in the register method of `app/Providers/Lara2faServiceProvider.php`:

```php
namespace App\Providers;

use MustafaAwami\Lara2fa\Lara2fa;

class Lara2faServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Lara2fa::ignoreRoutes();
    }
}
```

Here is the list of defined routes:

| Request                                                                 | Route Name                                | Description                                                                 |
|-------------------------------------------------------------------------|-------------------------------------------|-----------------------------------------------------------------------------|
| GET `/two-factor-challenge`                                             | `two-factor.login`                        | Show the two-factor authentication challenge view.                          |
| POST `/two-factor-challenge`                                            | -                                         | Submitting the two-factor authentication challenge form.                    |
| POST `/settings/authenticator-app-two-factor-authentication`            | `authenticator-app-two-factor.enable`     | Enable authenticator-app two-factor authentication for the authenticated user. |
| POST `/settings/confirmed-authenticator-app-two-factor-authentication`  | `authenticator-app-two-factor.confirm`    | Confirm authenticator-app two-factor authentication for the authenticated user. |
| DELETE `/settings/authenticator-app-two-factor-authentication`          | `authenticator-app-two-factor.disable`    | Disable authenticator-app two-factor authentication for the authenticated user. |
| GET `/settings/authenticator-app-two-factor-qr-code`                    | `authenticator-app-two-factor.qr-code`    | Get the SVG element for the user's two-factor authentication QR code.       |
| GET `/settings/authenticator-app-two-factor-secret-key`                 | `authenticator-app-two-factor.secret-key` | Get the current user's two-factor authentication setup / secret key.        |
| POST `/settings/email-two-factor-authentication`                        | `email-two-factor.enable`                 | Enable email two-factor authentication for the authenticated user.          |
| POST `/settings/confirmed-email-two-factor-authentication`              | `email-two-factor.confirm`                | Confirm email two-factor authentication for the authenticated user.         |
| DELETE `/settings/email-two-factor-authentication`                      | `email-two-factor.disable`                | Disable email two-factor authentication for the authenticated user.         |
| POST `/settings/email-two-factor-authentication-send-code`              | `email-two-factor.send-code`              | Send the OTP via email.                                                     |
| GET `/settings/passkeys-two-factor-authentication`                      | `passkeys-two-factor.get`                 | Get the user's passkeys.                                                    |
| GET `/settings/passkeys-two-factor-authentication-registerOptions`      | `passkeys-two-factor.getRegisterOptions`  | Get passkey registration options.                                           |
| POST `/settings/passkeys-two-factor-authentication`                     | `passkeys-two-factor.store`               | Create a new passkey for the authenticated user.                            |
| DELETE `/settings/passkeys-two-factor-authentication`                   | `passkeys-two-factor.disable`             | Delete all passkeys for the authenticated user.                             |
| DELETE `/settings/passkeys-two-factor-authentication/{passkey}/destroy` | `passkeys-two-factor.destroy`             | Delete the provided passkey for the authenticated user.                     |
| PUT `/settings/passkeys-two-factor-authentication/{passkey}/update`     | `passkeys-two-factor.update`              | Update the name of the provided passkey for the authenticated user.         |
| GET `/settings/two-factor-recovery-codes`                               | `two-factor-recovery-codes.get`           | Get the two-factor authentication recovery codes for the authenticated user.|
| POST `/settings/two-factor-recovery-codes`                              | `two-factor-recovery-codes.generate`      | Generate a fresh set of two-factor authentication recovery codes.           |
| DELETE `/settings/two-factor-recovery-codes`                            | `two-factor-recovery-codes.disable`       | Delete the two-factor authentication recovery codes for the authenticated user. |
| GET `/passkeys-two-factor/authenticateOptions`                          | `passkeys-two-factor.authenticateOptions` | Get passkey authentication options.                                         |
| POST `/passkeys-two-factor/authenticate`                                | `passkeys-two-factor.authenticate`        | Authenticate the user with the given passkey.                               |

You can customize the first part of the URL by setting the `prefix` value in the `lara2fa.php` config file.

## Rate Limiter

Lara2fa defines 3 rate limiters:

### 1- Passkey Login Attempts (passkey-login)
- This limiter protects the initial login/passkey verification endpoint.
- Purpose: Prevents rapid, repeated login attempts (brute-force) from a single user and/or IP address.
- Limit: 5 attempts per minute.

### 2- Two-Factor Email Notification (two-factor-email-notify)
- This limiter controls the "resend 2FA code" feature.
- Purpose: Prevents a user from spamming the system to send dozens of 2FA emails, which could abuse a mail service.
- Limit: 2 attempts per minute.

### 3- Two-Factor Code Verification (two-factor-login)
- This limiter protects the endpoint where the user submits their 2FA code.
- Purpose: Prevents a user from rapidly guessing the 6-digit (or similar) 2FA code.
- Limit: 5 attempts per minute.

You can find and customize the rate limiters inside the `boot` method of `\app\Providers\Lara2faServiceProvider.php`.

```php
namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Lara2faServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('passkey-login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request,array $headers) {

                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    Fortify::username() => [__('Too many attempts. Please try again after') . $seconds. __(' seconds')],
                ]);
            });
        });

        RateLimiter::for('two-factor-email-notify', function (Request $request) {

            $throttleKey = $request->session()->get('login.id');

            return Limit::perMinute(2)->by($throttleKey)->response(function (Request $request,array $headers) {

                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    'attempts' => [__('Too many attempts. Please try again after ') . $seconds. __(' seconds')],
                ])->errorBag('EmailTwoFactorAuthenticationNotification');
            });
        });

        RateLimiter::for('two-factor-login', function (Request $request) {

            $throttleKey = $request->session()->get('login.id');

            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request,array $headers) {
                $seconds = $headers['Retry-After'];

                throw ValidationException::withMessages([
                    'attempts' => [__('Too many attempts. Please try again after ') . $seconds. __(' seconds')],
                ]);
            });
        });
    }
}
```

You can also view them inside the `limiters` value in the `lara2fa.php` config file.

---

# 🚑 Troubleshooting & Important Notes

## ⚠️ Warning: File Overwrites

The installation process may publish files that overwrite existing files in your project (for example, configuration or resource files).
It’s strongly recommended to commit your changes or back up your project before running the install command.

Here is the list of files that will be published:

* `config/lara2fa.php`
* `database/migrations/2024_07_29_090549_add_two_factor_email_columns_to_users_table.php`
* `database/migrations/2025_09_10_081543_create_passkeys_table.php`
* `app/Providers/Lara2faServiceProvider.php`
* `app/Providers/FortifyServiceProvider.php`

Here are the React resource files that will be published if React is chosen:

* `resources/js/pages/settings/two-factor.tsx`
* `resources/js/pages/auth/login.tsx`
* `resources/js/pages/auth/two-factor-challenge.tsx`
* `resources/js/components/confirm-password-dialog.tsx`

Here are the Vue stack files that will be published if Vue is chosen:

* `resources/js/pages/settings/TwoFactor.vue`
* `resources/js/components/TwoFactorAuthenticatorApp.vue`
* `resources/js/components/TwoFactorEmail.vue`
* `resources/js/components/TwoFactorPasskeys.vue`
* `resources/js/components/TwoFactorRecoveryCodes.vue`
* `resources/js/pages/auth/Login.vue`
* `resources/js/pages/auth/TwoFactorChallenge.vue`
* `resources/js/components/ConfirmPasswordDialog.vue`

## Passkey Requirements

For Passkeys to work correctly, the following conditions must be met:
* Use a browser that supports WebAuthn (see: [https://caniuse.com/webauthn](https://caniuse.com/webauthn)).
* A proper domain (localhost and 127.0.0.1 will be rejected by webauthn.js).
* An SSL/TLS certificate trusted by your browser (self-signed is okay).
* An HTTPS connection on port 443 (ports other than 443 will be rejected) (use [Laravel Herd](https://herd.laravel.com/) to serve your sites over HTTPS).

---

# 👥 Contributing

Thank you for considering contributing to Lara2fa! You can read the contribution guide [here](.github/CONTRIBUTING.md).

---

# 🔒 Security Vulnerabilities

Please review the [security policy](.github/SECURITY.md) on how to report security vulnerabilities.

---

# ⚖️ License

The Lara2FA package is licensed under the **[MIT license](/LICENSE.md)**.