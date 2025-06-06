<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateZohoContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_type' => ['nullable', 'string', 'in:customer,vendor'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            // Адресные поля для биллинга
            'billing_address.attention' => ['nullable', 'string', 'max:255'],
            'billing_address.address' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.zip' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'max:255'],
            // Адресные поля для доставки
            'shipping_address.attention' => ['nullable', 'string', 'max:255'],
            'shipping_address.address' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.zip' => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'contact_name.required' => 'Имя контакта обязательно для заполнения.',
            'contact_name.string' => 'Имя контакта должно быть строкой.',
            'contact_name.max' => 'Имя контакта не должно превышать :max символов.',
            'email.email' => 'Введите действительный адрес электронной почты.',
            'contact_type.in' => 'Тип контакта должен быть "customer" или "vendor".',
        ];
    }
}
