<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param string $type Тип проверки (page|api)
     * @param string $section Раздел (например: finance)
     * @param string $action Действие (view|add|edit|delete)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $type, string $section, string $action): Response
    {

        $auth = auth()->user();

        // Если доступ к странице
        if ($type === 'page') {

            if (!$auth) {
                return Redirect::to('auth');
            }

            if (empty($auth->accesses[$section]->$action)) {
                return Redirect::to('/');
            }
        }

        // Если доступ через API
        if ($type === 'api') {

            if (!$auth) {
                return response()->apiError('Ошибка доступа - Авторизуйтесь в системе', 401);
            }

            if (empty($auth->accesses[$section]->$action)) {
                return response()->apiError('Недостаточно прав доступа к разделу / функции', 403);
            }
        }

        return $next($request);
    }

}