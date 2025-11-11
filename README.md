# 🔐 Lara2FA
<p>
<!-- <a href="https://github.com/laravel/fortify/actions"><img src="https://github.com/mustafa-awami/lara2fa/workflows/tests/badge.svg" alt="Build Status"></a> -->
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

## 🚀 Features

- ✅ Plug-and-play 2FA for Laravel 12
- 🔢 Compatible with Google Authenticator, Authy, and 1Password
- ✉️ Built-in Email OTP with configurable templates
- 🪪 WebAuthn support for FIDO2 devices, Windows Hello, Touch ID, and Passkeys
- 🧩 Easy install command with feature selection
- 🔒 Secure and standards-compliant implementation

---

## 🧰 Installation

Install via Composer:

```bash
composer require mustafa-awami/lara2fa -W
```

--- 

## ⚙️ Set Up

### Step 1️⃣

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

Then you will be asked which of the following 2fa methods would you like to enable:

```
 Which 2FA methods would you like to enable? (comma separated):
  [1] Authenticator App (TOTP)
  [2] Email OTP
  [3] Passkeys
```

Depending on the selected methods, the published `lara2fa.php` config file will be updated with enabling the selected methods and disabling the rest.

* **Note:** For Passkeys to work correctly, the following conditions must be met:
  - Use a browser that supports Webauthn.
  - a proper domain (localhost and 127.0.0.1 will be rejected by webauthn.js)
  - an SSL/TLS certificate trusted by your browser (self-signed is okay)
  - connected HTTPS on port 443 (ports other than 443 will be rejected)
  



#### ⚠️ Important Warning
The installation process may publish and overwrite existing files in your project if files with the same names already exist (for example: configuration or resource files).
It’s strongly recommended to commit your changes or back up your project before running the install command.

Here ara the list of files that will be published:

* `config/lara2fa.php`
* `database/migrations/2024_07_29_090549_add_two_factor_email_columns_to_users_table.php`
* `database/migrations/2025_09_10_081543_create_passkeys_table.php`
* `app/Providers/FortifyServiceProvider.php`

Here ara the react resource files that will be published if react is chosen:

* `resources/js/pages/settings/two-factor.tsx`
* `resources/js/pages/auth/login.tsx`
* `resources/js/pages/auth/two-factor-challenge.tsx`
* `resources/js/components/confirm-password-dialog.tsx`

Here ara the vue stack files that will be published if vue is chosen:

* `resources/js/pages/settings/TwoFactor.vue`
* `resources/js/components/TwoFactorAuthenticatorApp.vue`
* `resources/js/components/TwoFactorEmail.vue`
* `resources/js/components/TwoFactorPasskeys.vue`
* `resources/js/components/TwoFactorRecoveryCodes.vue`
* `resources/js/pages/auth/Login.vue`
* `resources/js/pages/auth/TwoFactorChallenge.vue`
* `resources/js/components/ConfirmPasswordDialog.vue`

### Step 2️⃣

In `User.php` model, replace:

```php
use Laravel\Fortify\TwoFactorAuthenticatable;
```

with:

```php
use MustafaAwami\Lara2fa\Traits\TwoFactorAuthenticatable;
```

### Step 3️⃣

In `settings.php` route file, replace:

```php
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
```

with:

```php
use MustafaAwami\Lara2fa\Http\Controllers\Settings\TwoFactorAuthenticationController;
```

### Step 4️⃣

In `fortify.php` config file, disable the two factor feature by comminting it out like so:

```php
// Features::twoFactorAuthentication([
//     'confirm' => true,
//     'confirmPassword' => true,
//     // 'window' => 0,
// ]),
```

### Step 5️⃣

run `php artisan migrate` to migrate the newly add tables.

--- 

## 🔧 Configuration

### Routs
