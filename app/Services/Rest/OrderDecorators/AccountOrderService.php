<?php

namespace App\Services\Rest\OrderDecorators;

use App\Models\Client;
use App\Models\Comment;
use App\Models\User;
use App\Services\Rest\Contracts\OrderAfterCreateServiceContract;
use App\Services\Service;

class AccountOrderService extends Service implements OrderAfterCreateServiceContract
{
    public function handle(array $inp): void
    {

        // Добавляем клиента в базу
        (new Client())->insert([
            'fio'   => $inp['user_fio'],
            'phone' => $inp['user_phone'],
        ]);

        // Не требуется регистрация
        if (empty($inp['order_reg'])) {
            return;
        }

        // Создаем аккаунт
        $dataUserInsert = (new User())->reg([
            'mail'   => null,
            'phone'  => $inp['user_phone'],
            'name'   => $inp['user_fio'],
            'city'   => null,
            'access' => null,
            'store'  => null,
            'moder'  => self::STATUS_NOACTIVE,
        ]);

        if ($dataUserInsert) {

            // Добавляем комментарий
            (new Comment())->add([
                'user' => self::USER_SYSTEM_ID,
                'type' => 'order',
                'post' => $inp['order_id'],
                'text' => 'На основании заказа НЕ получилось создать аккаунт пользователя',
            ]);

            return;

        }

        // Добавляем комментарий
        (new Comment())->add([
            'user' => self::USER_SYSTEM_ID,
            'type' => 'order',
            'post' => $inp['order_id'],
            'text' => 'На основании заказа создан аккаунт пользователя',
        ]);

    }
}
