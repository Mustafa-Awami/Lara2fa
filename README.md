<p align="center"><img width="500" height="185" src="/art/logo.png" alt="Logo Lara2fa"></p>

<p align="center">
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/v/mustafa-awami/lara2fa" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/dt/mustafa-awami/lara2fa" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/mustafa-awami/lara2fa"><img src="https://img.shields.io/packagist/l/mustafa-awami/lara2fa" alt="License"></a>
<a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-12%2B-red?logo=laravel" alt="Laravel 12+"></a>
  
</p>

**Lara2FA** is a modern, flexible, and developer-friendly **Two-Factor Authentication (2FA)** package for Laravel.  
It supports **three powerful authentication methods** out of the box:

- ✉️ **Email OTP**
- 🔢 **Authenticator Apps (TOTP)**
- 🪪 **WebAuthn (Passkeys / Security Keys / Biometrics)**

Designed for simplicity, security, and seamless integration into any Laravel project.

---

# 🚀 Features

- ✅ Plug-and-play 2FA for Laravel 12
- 🔢 Compatible with Google Authenticator, Authy, and 1Password
- ✉️ Built-in Email OTP with configurable templates
- 🪪 WebAuthn support for FIDO2 devices, Windows Hello, Touch ID, and Passkeys
- 🧩 Easy install command with feature selection
- 🔒 Secure and standards-compliant implementation

---

# 📝 Minimum Requirements

* Laravel Framework 12.37.0

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

Depending on the selected methods, the published `lara2fa.php` config file will be updated with enabling the selected methods and disabling the rest.

* **Note:** For Passkeys to work correctly, the following conditions must be met:
  - Use a browser that supports WebAuthn.
  - A proper domain (localhost and 127.0.0.1 will be rejected by webauthn.js).
  - An SSL/TLS certificate trusted by your browser (self-signed is okay).
  - An HTTPS connection on port 443 (ports other than 443 will be rejected).
  
### ⚠️ Important Warning
The installation process may publish and overwrite existing files in your project if files with the same names already exist (for example: configuration or resource files).
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

Run the migration command to migrate the newly added tables:
```bash
php artisan migrate
```
Finally, run the build command:
```bash
npm run build
```
--- 

# 🔧 Configuration (Optional)

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

You can customize the first part of the URL by setting the 'prefix' value in the `lara2fa.php` config file.

```
