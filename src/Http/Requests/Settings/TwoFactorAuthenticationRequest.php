<?php

namespace MustafaAwami\Lara2fa\Http\Requests\Settings;

use MustafaAwami\Lara2fa\Features;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorAuthenticationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Features::canManagetwoFactorAuthentication();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
