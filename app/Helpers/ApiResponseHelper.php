<?php



use Illuminate\Http\JsonResponse;

if (!function_exists('apiError')) {
    /**
     * Возвращает стандартный JSON-ответ об ошибке
     *
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    function apiError(string $message, int $code = 422): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code, [], JSON_UNESCAPED_UNICODE);

    }
}

if (!function_exists('apiSuccess')) {
    /**
     * Возвращает стандартный JSON-ответ об успехе
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    function apiSuccess(mixed $data = null, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
