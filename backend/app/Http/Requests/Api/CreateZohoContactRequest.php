<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Класс запроса для валидации данных при создании нового контакта в Zoho Inventory.
 */
class CreateZohoContactRequest extends FormRequest
{
    /**
     * Определяет, разрешено ли пользователю выполнять этот запрос.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Для API-запросов, если пользователь уже аутентифицирован (например, через токен Zoho,
        // который проверяется в контроллере), мы разрешаем запрос.
        return true;
    }

    /**
     * Получает правила валидации, которые применяются к запросу.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_type' => ['nullable', 'string', 'in:customer,vendor'], // Опционально, если явно указывать тип
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            // Адреса - можно сделать вложенной валидацией
            'billing_address.attention' => ['nullable', 'string', 'max:255'],
            'billing_address.address' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.zip' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'max:255'],
            'shipping_address.attention' => ['nullable', 'string', 'max:255'],
            'shipping_address.address' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.zip' => ['nullable', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Получает пользовательские сообщения об ошибках валидации.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'contact_name.required' => 'Имя контакта обязательно для заполнения.',
            'contact_name.string' => 'Имя контакта должно быть строкой.',
            'contact_name.max' => 'Имя контакта не должно превышать :max символов.',
            'email.email' => 'Введите действительный адрес электронной почты.',
            'contact_type.in' => 'Тип контакта должен быть "customer" или "vendor".',
            // Вы можете добавить более специфичные сообщения для адресов, если потребуется.
        ];
    }
}
