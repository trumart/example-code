<?php



namespace App\Services\Route;

use App\Models\Finance\Finance;
use App\Models\Route\Loader;
use App\Models\RouteParam;
use App\Models\Store;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class LoaderService extends Service
{
    /**
     * Список машин, по которым еще не было выплат
     *
     * @param array $inp
     * @return array|JsonResponse
     */
    public function getRoutesParam(array $inp = []): array|JsonResponse
    {

        $arrRoutes = [];

        $auth = auth()->user();

        if (!empty($auth->store)) {

            // Подразделене
            $store = (new Store())->getStore(['id' => $auth->store]);

            if (in_array($store->type, ['магазин', 'пункт выдачи'])) {
                $inp['store_id'] = $store->id;
            }

        }

        $inp['date_start'] ??= Carbon::now()->subDays(10)->format('Y-m-d');

        $routesParam = (new RouteParam())->getParams([
            'store'      => $inp['store_id'] ?? null,
            'date_start' => $inp['date_start'],
        ]);

        foreach ($routesParam as $routeParam) {

            // Проверяем были ли уже оплата по данной машине
            $loader = (new Loader())->getLoader([
                'route_param_id' => $routeParam->id,
                'store_id'       => $auth->store ?? null,
            ]);

            if (!empty($loader)) {
                continue;
            }

            $arrRoutes[] = [
                'id'          => $routeParam->id,
                'car'         => $routeParam->car,
                'driver'      => $routeParam->driver,
                'dateDisplay' => $routeParam->datePrepare,
            ];

        }

        return $arrRoutes;

    }

    /**
     * Фиксация работы грузчиков и создания расходника
     *
     * @param array $inp
     * @return JsonResponse
     */
    public function insert(array $inp): JsonResponse
    {

        $auth = auth()->user();

        $inp['store_id'] ??= $auth->store;

        // Проверяем были ли уже оплата по данной машине
        $loader = (new Loader())->getLoader([
            'route_param_id' => $inp['route_param_id'],
            'store_id'       => $inp['store_id'],
        ]);

        if (!empty($loader)) {
            return apiError('Подожди, но оплата грузчиков за данную машину уже была');
        }

        // Фиксируем грузчиков
        $insert = (new Loader())->insert([
            'route_param_id' => $inp['route_param_id'],
            'store_id'       => $inp['store_id'],
            'price'          => $inp['price'],
            'worker'         => $inp['worker'],
            'hour'           => $inp['hour'],
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        if (!isset($inp['check_indrive']) || $inp['check_indrive'] !== true) {
            return apiSuccess([], 'Работы зафиксированы');
        }

        $insert = (new Finance())->insert([
            'user_id'              => $auth->id,
            'date'                 => date('Y-m-d'),
            'store_id'             => $inp['store_id'],
            'store_cash_id'        => $inp['store_id'],
            'cashbox'              => 2,
            'paycash'              => 1,
            'type'                 => 'расход',
            'cat_id'               => 58, // Грузчики
            'title'                => "Разгрузка рейса {$inp['route_param_id']}",
            'text'                 => "Время работы {$inp['hour']} часа, кол-во грузчиков {$inp['worker']}",
            'sum'                  => $inp['price'],
            'view'                 => 1,
            'moder_store'          => $inp['store_id'],
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => null,
            'moder_manager_status' => null,
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return apiSuccess([], 'Расход добавлен, можно выплатить д/c');

    }
}
