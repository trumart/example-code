<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ApiResponseServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов.
     */
    public function register(): void
    {
        //
    }

    /**
     * Загрузка сервисов.
     */
    public function boot(): void
    {
        // Ошибка
        Response::macro('apiError', fn (string $message, int $code = 422) => response()->json([
                'status'  => 'error',
                'message' => $message,
            ], $code, [], JSON_UNESCAPED_UNICODE));

        // Успех
        Response::macro('apiSuccess', fn ($data = null, string $message = 'OK') => response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE));
    }
}
