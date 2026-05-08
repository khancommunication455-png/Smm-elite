<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'method' => 'required|in:easypaisa,jazzcash,crypto,pm',
            'amount' => [
                'required',
                'numeric',
                'min:' . (config('services.payments.min_deposit', 100)),
                'max:' . (config('services.payments.max_deposit', 500000)),
            ],
            'reference' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => 'Please select a payment method.',
            'method.in' => 'Invalid payment method.',
            'amount.required' => 'Please enter an amount.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Minimum deposit is PKR ' . env('MIN_DEPOSIT_AMOUNT', 100),
            'amount.max' => 'Maximum deposit is PKR ' . env('MAX_DEPOSIT_AMOUNT', 500000),
            'reference.required' => 'Please provide a reference/transaction ID.',
            'reference.max' => 'Reference cannot exceed 100 characters.',
            'reference.regex' => 'Reference can only contain letters, numbers, hyphens, and underscores.',
        ];
    }
}
