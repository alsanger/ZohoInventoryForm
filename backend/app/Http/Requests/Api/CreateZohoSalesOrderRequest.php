<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Класс запроса для валидации данных при создании нового заказа на продажу в Zoho Inventory.
 */
class CreateZohoSalesOrderRequest extends FormRequest
{
    /**
     * Определяет, разрешено ли пользователю выполнять этот запрос.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Аналогично CreateZohoContactRequest, авторизация Zoho проверяется в контроллере.
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
            'customer_id' => ['required', 'string', 'max:255'], // ID клиента в Zoho Inventory
            'date' => ['nullable', 'date_format:Y-m-d'], // Дата заказа в формате ГГГГ-ММ-ДД
            'notes' => ['nullable', 'string', 'max:500'], // Примечания к заказу
            'terms_and_conditions' => ['nullable', 'string', 'max:1000'], // Условия и положения

            // Правила для позиций заказа (line_items)
            'line_items' => ['required', 'array', 'min:1'], // Должен быть массив, содержащий хотя бы один элемент
            'line_items.*.item_id' => ['required', 'string', 'max:255'], // ID товара в Zoho Inventory
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'], // Количество товара
            'line_items.*.rate' => ['required', 'numeric', 'min:0'], // Цена за единицу товара
            'line_items.*.description' => ['nullable', 'string', 'max:500'], // Описание позиции
            'line_items.*.item_custom_fields' => ['nullable', 'array'], // Дополнительные кастомные поля для позиции
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
            'customer_id.required' => 'ID клиента обязателен.',
            'line_items.required' => 'Заказ должен содержать хотя бы одну позицию.',
            'line_items.array' => 'Позиции заказа должны быть массивом.',
            'line_items.min' => 'Заказ должен содержать минимум :min позицию.',
            'line_items.*.item_id.required' => 'ID товара в каждой позиции обязателен.',
            'line_items.*.quantity.required' => 'Количество товара в каждой позиции обязательно.',
            'line_items.*.quantity.numeric' => 'Количество товара должно быть числом.',
            'line_items.*.quantity.min' => 'Количество товара должно быть больше нуля.',
            'line_items.*.rate.required' => 'Цена за единицу товара в каждой позиции обязательна.',
            'line_items.*.rate.numeric' => 'Цена за единицу товара должна быть числом.',
            'line_items.*.rate.min' => 'Цена за единицу товара не может быть отрицательной.',
        ];
    }
}
