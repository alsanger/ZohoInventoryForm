<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ZohoAuthCallbackRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения этого запроса.
     *
     * Для callback-запроса от Zoho, авторизация не требуется, так как это внешний вызов.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Разрешаем всем выполнять этот запрос, так как это callback от Zoho
    }

    /**
     * Получает правила валидации, которые применяются к запросу.
     *
     * Zoho отправит 'code' (код авторизации) и 'location' (домен датацентра Zoho, например 'eu').
     * 'code' является обязательным, 'location' - опциональным, но рекомендуется его валидировать.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'], // Код авторизации от Zoho - обязателен
            'location' => ['nullable', 'string', 'in:us,eu,in,cn,au,jp,ca'], // Домен датацентра, опционально, но должен быть одним из перечисленных
        ];
    }

    /**
     * Получает сообщения об ошибках для определенных правил валидации.
     *
     * @return array<string, string>
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
