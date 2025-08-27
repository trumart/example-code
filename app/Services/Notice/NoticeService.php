<?php

namespace App\Services\Notice;

use App\Events\NoticeCreated;
use App\Models\Notice;
use App\Models\User;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class NoticeService extends Service
{
    /**
     * Добавление уведомления
     *
     * @param array $inp
     * @return JsonResponse
     */
    public function insert(array $inp): JsonResponse
    {

        $auth = auth()->user();

        $inp['no_user'] ??= false;

        // Если нет доступа, то уведомление самому себе
        if (!$auth->accesses['task']->edit) {

            // Кому уведомление - пользователь
            $inp['user'] = $auth->id;

            // Кому уведомление - склад
            $inp['store'] = explode(',', $auth->store);
            $inp['store'] = $inp['store'][0];

        }

        if (!empty($inp['date'])) {
            $inp['date'] = Carbon::parse($inp['date'])->addHours(3)->format('Y-m-d H:i:s');
        }

        // Всем пользователям подразделения
        if ($inp['no_user'] === true) {

            $inp['user']  = 0;
            $inp['store'] = $auth->store;

        }

        if (empty($inp['user']) && empty($inp['store'])) {
            $inp['user'] = $auth->id;
        }

        return (new Notice())->add([
            'date'    => $inp['date']  ?? null,
            'user'    => $inp['user']  ?? 0,
            'store'   => $inp['store'] ?? 0,
            'access'  => null,
            'title'   => 'Уведомление',
            'type'    => 'danger',
            'message' => $inp['text'],
        ]);

    }

    /**
     * Кому отправляем уведомления
     *
     * @param Notice $notice
     * @return void
     */
    public function dispatchEvents(Notice $notice): void
    {

        $data = $notice->toArray();

        $userIds = [];

        if (!empty($notice->user)) {
            $userIds[] = $notice->user;
        }

        if (!empty($notice->store)) {

            $userIds = array_merge(
                $userIds,
                (new User())->getStoreUsers([
                    'store' => $notice->store,
                    'field' => 'ids'
                ])->pluck('id')->toArray()
            );

        }

        foreach (array_unique($userIds) as $userId) {

            event(new NoticeCreated($data, $userId));

        }

    }
}
