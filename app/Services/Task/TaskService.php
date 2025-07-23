<?php

namespace App\Services\Task;

use App\Models\Tasks;
use App\Services\Service;

class TaskService extends Service
{
    /**
     * Создание задачи, если еще не существует.
     *
     * @param array $search — Параметры поиска (один или два набора условий)
     * @param array $inp — Параметры новой задачи
     * @return mixed|null — ID задачи или null
     */
    public function createTaskIfNotExists(array $search, array $inp)
    {

        // Проверка двух условий поиска
        if (isset($search[0]) && is_array($search[0])) {

            foreach ($search as $condition) {
                if ((new Tasks())->getTask($condition)) {
                    return null;
                }
            }

        } else {

            // Один набор условий
            if ((new Tasks())->getTask($search)) {
                return null;
            }

        }

        // Значения по умолчанию
        $defaults = [
            'store'       => null,
            'user'        => null,
            'cat'         => 'task',
            'date'        => date('Y-m-d'),
            'date_finish' => null,
            'type'        => 'Заказ',
            'code'        => null,
            'item'        => null,
            'url'         => null,
            'url_name'    => null,
            'price'       => null,
            'moder_user'  => self::USER_SYSTEM_ID,
        ];

        $data = array_merge($defaults, $inp);

        return (new Tasks())->insert(auth()->user(), $data);

    }
}
