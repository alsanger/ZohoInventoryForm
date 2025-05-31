<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Модель для хранения токенов авторизации Zoho.
 *
 * Хранит access_token, refresh_token и время истечения токена.
 *
 * @property int $id Уникальный идентификатор записи.
 * @property string $access_token Токен доступа для API Zoho.
 * @property string $refresh_token Токен для обновления access_token.
 * @property Carbon $expires_at Дата и время истечения токена доступа (объект Carbon).
 * @property Carbon $created_at Дата и время создания записи.
 * @property Carbon $updated_at Дата и время последнего обновления записи.
 */
class ZohoToken extends Model
{
    use HasFactory;

    /**
     * Поля, которые разрешено массово присваивать (mass assignable).
     * Это повышает безопасность, предотвращая случайное изменение других полей.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * Определяет, как должны быть приведены (cast) атрибуты модели при их доступе.
     * Например, 'expires_at' будет автоматически преобразовано в объект Carbon,
     * что упрощает работу с датами и временем.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
