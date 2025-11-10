<?php

namespace MustafaAwami\Lara2fa\Traits;

use BaconQrCode\Writer;
use MustafaAwami\Lara2fa\Features;
use BaconQrCode\Renderer\Color\Rgb;
use MustafaAwami\Lara2fa\Models\Passkey;
use Illuminate\Support\Facades\Crypt;
use BaconQrCode\Renderer\ImageRenderer;
use Illuminate\Database\Eloquent\Model;
use MustafaAwami\Lara2fa\Services\RecoveryCode;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Illuminate\Database\Eloquent\Relations\HasMany;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Laravel\Fortify\Fortify;
use MustafaAwami\Lara2fa\Notifications\emailTwoFactorCode;
use MustafaAwami\Lara2fa\Contracts\AuthenticatorAppTwoFactorAuthenticationProvider;

trait TwoFactorAuthenticatable
{
    /**
     * Determine if two-factor authentication has been enabled.
     *
     * @return bool
     */
    public function hasEnabledTwoFactorAuthentication()
    {
        return $this->hasEnabledAuthenticatorAppTwoFactorAuthentication() ||
                $this->hasEnabledEmailTwoFactorAuthentication() ||
                $this->hasEnabledPasskeyAuthentication();
    }

    /**
     * Determine if authenticator app two-factor authentication has been enabled.
     *
     * @return bool
     */
    public function hasEnabledAuthenticatorAppTwoFactorAuthentication()
    {
        if (Features::confirmsAuthenticatorAppTwoFactorAuthentication()) {

            return (! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at));
        }

        return ! is_null($this->two_factor_secret);
    }

    /**
     * Determine if email two-factor authentication has been enabled.
     *
     * @return bool
     */
    public function hasEnabledEmailTwoFactorAuthentication()
    {
        if (Features::confirmsEmailTwoFactorAuthentication()) {

            return (! is_null($this->email_two_factor_enabled_at) && ! is_null($this->email_two_factor_confirmed_at));
        }

        return ! is_null($this->email_two_factor_enabled_at);
    }

    /**
     * Determine if two-factor recovery codes has been enabled.
     *
     * @return bool
     */
    public function hasEnabledTwoFactorRecoveryCodes()
    {
        return ! is_null($this->two_factor_recovery_codes);
    }



    /**
     * Test if the user can register a new passkey.
     */
    public function canRegisterPasskey(): bool
    {
        return Features::enabled(Features::passkeys()) && ($this->passkeysCount() < config('lara2fa-options.passkeys.max_passkeys', 3));
    }

    /**
     * Determine if passkey authentication has been enabled.
     *
     * @return bool
     */
    public function hasEnabledPasskeyAuthentication()
    {
        return Features::enabled(Features::passkeys()) && $this->hasPasskey();
    }

    /**
     * Detect if user has a key.
     */
    public function hasPasskey(): bool
    {
        return $this->passkeysCount() > 0;
    }

    public function passkeysCount(): int
    {
        return Passkey::where('user_id', $this->id)->count();
    }







    /**
     * Get the user's two factor authentication recovery codes.
     *
     * @return array
     */
    public function recoveryCodes()
    {
        return json_decode((Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($this->two_factor_recovery_codes), true);
    }

    /**
     * Replace the given recovery code with a new one in the user's stored codes.
     *
     * @param  string  $code
     * @return void
     */
    public function replaceRecoveryCode($code)
    {
        $this->forceFill([
            'two_factor_recovery_codes' => (Model::$encrypter ?? Crypt::getFacadeRoot())->encrypt(str_replace(
                $code,
                RecoveryCode::generate(),
                (Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($this->two_factor_recovery_codes)
            )),
        ])->save();
    }

    /**
     * Get the QR code SVG of the user's two factor authentication QR code URL.
     *
     * @return string
     */
    public function twoFactorQrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->twoFactorQrCodeUrl());

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * Get the two factor authentication QR code URL.
     *
     * @return string
     */
    public function twoFactorQrCodeUrl()
    {
        return app(AuthenticatorAppTwoFactorAuthenticationProvider::class)->qrCodeUrl(
            config('app.name'),
            $this->{Fortify::username()},
            (Model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($this->two_factor_secret)
        );
    }

    /**
     * Reset the email two factor code.
     */
    public function resetEmailTwoFactorCode()
    {
        $this->forceFill([
            'email_two_factor_code' => null,
            'email_two_factor_code_expires_at' => null,
        ])->save();
    }

    public function sendEmailTwoFactorCode($code, $actionUrl = null)
    {
        $this->notify(new emailTwoFactorCode($code, $actionUrl));
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    public function passkeysCollection()
    {
        return $this->passkeys()
                            ->get()
                            ->map(fn ($key) => [
                                'id' => $key->id,
                                'name' => $key->name,
                                'created_at' => $key->created_at->diffForHumans(),
                                'updated_at' => $key->updated_at->diffForHumans(),
                            ]);
    }
}
