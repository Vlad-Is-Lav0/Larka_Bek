<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSettings extends Model
{
    use HasFactory;

    // Указываем название таблицы (если оно не совпадает с названием модели)
    protected $table = 'main_settings';

    // Указываем, какие поля можно заполнять (защищаем от массового назначения)
    protected $fillable = [ 'accountId', 'ms_token', 'UID_ms'];

    public $timestamps = false; // Если таблица не использует поля created_at и updated_at
}
