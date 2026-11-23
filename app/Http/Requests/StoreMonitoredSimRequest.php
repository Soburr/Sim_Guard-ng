<?php

namespace App\Http\Requests;

use App\Support\NigerianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoredSimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = NigerianPhone::normalize($this->input('phone_number'));

        if ($normalized) {
            $this->merge([
                'phone_number' => $normalized,
                'telco' => $this->input('telco') ?: NigerianPhone::detectTelco($normalized),
            ]);
        }
    }

    public function rules(): array
    {
        $validBanks = array_keys(config('nigerian_banks', []));

        return [
            'label' => ['nullable', 'string', 'max:60'],
            'phone_number' => [
                'required',
                'regex:/^\+234(70|80|81|90|91)\d{8}$/',
                Rule::unique('monitored_sims', 'phone_number')
                    ->where('user_id', $this->user()->id),
            ],
            'telco' => ['required', Rule::in(['mtn', 'airtel', 'glo', '9mobile'])],
            'mode' => ['required', Rule::in(['keep_alive', 'watchdog'])],
            'last_used_days_ago' => ['nullable', 'integer', 'min:0', 'max:170'],
            'bank_keys' => ['required', 'array', 'min:1'],
            'bank_keys.*' => ['string', Rule::in($validBanks)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Enter a valid 11-digit Nigerian mobile number (e.g., 0803 123 4567).',
            'phone_number.unique' => 'You have already added this phone number to your SIM Vault.',
            'bank_keys.required' => 'Select at least one bank linked to this SIM so we can prepare its USSD kill-switch.',
        ];
    }
}