<?php

namespace App\Services\Finance;

use App\Models\Finance\Finance;
use Illuminate\Http\JsonResponse;

class ChildService extends BaseService
{
    /**
     * Создание фин. операции
     *
     * @param int $inp
     * @return JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function insert(array $inp, Finance $finance = null): JsonResponse
    {

        if (empty($finance)) {
            $finance = (new Finance())->getOperation(['id' => $inp['parent_id']]);
        }

        if (empty($finance)) {
            return response()->apiError('Финансовая операция не найдена');
        }

        $insert = (new Finance())->insert([
            'parent_id'            => $finance->id,
            'code'                 => $inp['code'],
            'date'                 => null,
            'store_id'             => 0,
            'store_cash_id'        => 0,
            'cashbox'              => 0,
            'type'                 => $finance->type,
            'paycash'              => null,
            'cat_id'               => null,
            'title'                => $inp['title'],
            'text'                 => $inp['quantity'],
            'sum'                  => $inp['price'],
            'view'                 => null,
            'doc_num'              => null,
            'doc_date'             => null,
            'doc_type'             => null,
            'nds'                  => null,
            'nds_val'              => null,
            'user_id'              => null,
            'moder_store'          => null,
            'moder_store_status'   => null,
            'moder_manager'        => null,
            'moder_manager_status' => null,
        ]);

        if (!$insert) {
            return response()->apiError($insert);
        }

        return response()->apiSuccess($insert);

    }
}
