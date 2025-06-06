<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZohoAuthCallbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Разрешаем всем, это callback от Zoho.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Код авторизации от Zoho обязателен.
            'code' => ['required', 'string'],
            // Домен датацентра, опционально.
            'location' => ['nullable', 'string', 'in:us,eu,in,cn,au,jp,ca'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Код авторизации от Zoho не был предоставлен.',
            'code.string' => 'Код авторизации должен быть строкой.',
            'location.in' => 'Недопустимое значение для региона Zoho.',
        ];
    }
}
