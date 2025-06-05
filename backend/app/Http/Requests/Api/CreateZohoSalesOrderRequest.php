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
            'customer_id' => ['required', 'string'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:500'],
            'terms_and_conditions' => ['nullable', 'string', 'max:1000'],

            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.item_id' => ['required', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.rate' => ['required', 'numeric', 'min:0'],
            'line_items.*.description' => ['nullable', 'string', 'max:500'],
            'line_items.*.item_custom_fields' => ['nullable', 'array'],
            'line_items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'create_purchase_orders_for_deficit' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'ID клиента обязателен.',
            'customer_id.string' => 'ID клиента должен быть строкой.',
            'line_items.required' => 'Заказ должен содержать хотя бы одну позицию.',
            'line_items.array' => 'Позиции заказа должны быть массивом.',
            'line_items.min' => 'Заказ должен содержать минимум :min позицию.',
            'line_items.*.item_id.required' => 'ID товара в каждой позиции обязателен.',
            'line_items.*.item_id.string' => 'ID товара в каждой позиции должен быть строкой.',
            'line_items.*.quantity.required' => 'Количество товара в каждой позиции обязательно.',
            'line_items.*.quantity.numeric' => 'Количество товара должно быть числом.',
            'line_items.*.quantity.min' => 'Количество товара должно быть больше нуля.',
            'line_items.*.rate.required' => 'Цена за единицу товара в каждой позиции обязательна.',
            'line_items.*.rate.numeric' => 'Цена за единицу товара должна быть числом.',
            'line_items.*.rate.min' => 'Цена за единицу товара не может быть отрицательной.',
            'line_items.*.discount_amount.numeric' => 'Сумма скидки должна быть числом.',
            'line_items.*.discount_amount.min' => 'Сумма скидки не может быть отрицательной.',
        ];
    }
}
