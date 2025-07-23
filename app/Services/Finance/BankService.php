<?php

namespace App\Services\Finance;

use App\Jobs\Finance\FinanceJob;
use App\Models\Contr;
use App\Models\Finance\Finance;
use App\Models\Notice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\OrderBidsPay;
use App\Models\OrderCredit;
use App\Models\Pay;
use App\Models\Store;
use App\Models\StoreTerminal;
use App\Models\User;
use App\Models\UserDay;
use App\Models\UserWorker;
use App\Services\Task\TaskService;

class BankService extends BaseService
{
    protected string $folder = 'bank'; // Папка для загрузки выписок банка

    /**
     * Распределение оплат сотрудникам зп по банку
     */
    public function distributeUserWorkerWagePay(): void
    {

        $financeModel = new Finance();

        // Все операции
        $finances = $financeModel->getOperations([
            'cat_id'         => self::CAT_BANK_OPERATION,
            'type'           => 'расход',
            'nodistribution' => true,
            'date_type'      => 'created_at',
            'date_start'     => '2025-07-01 00:00:00',
        ]);

        foreach ($finances as $finance) {

            if (empty($finance->inn)) {
                continue;
            }

            // Сотрудник
            $worker = (new UserWorker())->getUserWorker([
                'inn' => $finance->inn,
            ]);

            if (empty($worker)) {
                continue;
            }

            // Пользователь
            $user = (new User())->getUser(['id' => $worker->user]);

            if (empty($user)) {
                continue;
            }

            $insert = $financeModel->insert([
                'code'                 => $finance->code,
                'setting_id'           => $finance->setting_id,
                'store_id'             => $user->store ?? self::STORE_ID_OFFICE,
                'store_cash_id'        => self::STORE_ID_OFFICE,
                'cashbox'              => $finance->cashbox,
                'type'                 => $finance->type,
                'paycash'              => $finance->paycash,
                'date'                 => $finance->date,
                'title'                => $finance->title,
                'text'                 => $finance->text,
                'cat_id'               => self::CAT_MAIN_WAGE,
                'num'                  => $finance->num,
                'inn'                  => $finance->inn,
                'sum'                  => $finance->sum,
                'user_id'              => $finance->user_id,
                'user_pay_id'          => $worker->user,
                'view'                 => 0,
                'distribution'         => $finance->id,
                'moder_store'          => $finance->moder_store,
                'moder_store_status'   => 1,
                'moder_manager'        => $finance->moder_manager,
                'moder_manager_status' => 1,
            ]);

            if (!$insert) {
                continue;
            }

            // Поступление участвовало в проверке
            (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $insert->id,
            ]);

        }

        echo 'continue';

    }

    /**
     * Распределение оплат контрагентам
     */
    public function distributeContrPay($auth): void
    {

        // Все операции
        $finances = (new Finance())->getOperations([
            'cat_id'         => self::CAT_BANK_OPERATION,
            'type'           => 'расход',
            'nodistribution' => true,
        ]);

        foreach ($finances as $finance) {

            if (empty($finance->inn)) {
                continue;
            }

            // Ищем контрагента с данным ИНН
            $contr = (new Contr())->getContr([
                'inn'   => $finance->inn,
                'moder' => self::STATUS_ACTIVE
            ]);

            if (empty($contr)) {
                continue;
            }

            // Подразделение контрагента
            $store = (new Store())->getStore([
                'contr' => $contr->id,
                'moder' => self::STATUS_ACTIVE,
            ]);

            if (empty($store)) {
                continue;
            }

            // Проверяем, есть ли такой платеж уже в дебиторке
            $pay = (new OrderBidsPay())->getPay([
                'bid'          => 0,
                'setting'      => $finance->setting,
                'type'         => 'безнал',
                'score'        => 'платеж_' . $finance->num,
                'store_sender' => $store->id,
                'moder'        => self::STATUS_ACTIVE,
                'date'         => $finance->date,
            ]);

            if (!empty($pay)) {
                continue;
            }

            // Добавляем в дебиторку
            (new OrderBidsPay())->insert($auth, [
                'bid'          => 0,
                'setting'      => $finance->setting,
                'type'         => 'безнал',
                'score'        => 'платеж_' . $finance->num,
                'amount'       => $finance->sum,
                'amount_start' => $finance->sum,
                'store'        => $store->id,
                'cancel'       => self::STATUS_ACTIVE,
                'cancel_type'  => 'Оплата с банка',
                'moder'        => self::STATUS_ACTIVE,
                'moder_user'   => self::USER_SYSTEM_ID,
                'date'         => $finance->date,
            ]);

            // Поступление участвовало в проверке
            (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $finance->id,
            ]);

        }

        echo 'continue';

    }

    /**
     * Распределение оплат по кредитным заказам
     */
    public function distributeOrderCreditPay($auth): void
    {

        // Заказы безнальные
        $orders = (new Order())->getOrders($auth, [
            'search' => [
                'status'   => ['Новый', 'Думает', 'Не дозвонили', 'Подтвержден', 'В обработке', 'В пути', 'Поступил'],
                'pay_type' => 'кредит',
            ]
        ], true);

        foreach ($orders as $order) {

            // Данные по кредиту
            $credit = (new OrderCredit())->getOrderCredit([
                'code' => $order->code
            ]);

            if (empty($credit)) {
                continue;
            }

            // Ищем операцию с оплатой данного заказа
            $finance = (new Finance())->getOperation([
                'text'           => '%' . $credit->number . '%',
                'cat_id'         => self::CAT_BANK_OPERATION, // Банк
                'type'           => 'приход',
                'nodistribution' => true,
            ]);

            if (empty($finance)) {
                continue;
            }

            // Добавляем оплату
            $payId = (new Pay())->insert($auth, [
                'orderId'        => $order->id,
                'type'           => 'кредит',
                'check'          => $finance->num,
                'status'         => 'Оплачено',
                'amount'         => ((float) ($finance->sum) * 100), // Сумма фактическая которая поступила на счет
                'amount_deposit' => ((float) ($finance->sum) * 100),
                'moder'          => self::STATUS_NOACTIVE,
                'moder_user'     => self::USER_SYSTEM_ID,
            ]);

            if (!is_numeric($payId)) {
                continue;
            }

            // Поступление участвовало в проверке
            (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $order->id,
            ]);

            // Подтверждаем оплату
            (new Pay())->accept($auth, [
                'id' => $payId
            ]);

        }

        echo 'continue';

    }

    /**
     * Распределение оплат по терминалу
     */
    public function distributeSberTerminalPay($auth, array $inp = []): void
    {

        if (empty($inp['date'])) {
            $inp['date'] = date('Y-m-d', strtotime('-2 day'));
        }

        // Коды терминалов
        $terminals = (new StoreTerminal())->getTerminals();

        foreach ($terminals as $terminal) {

            $arrUser = [];

            echo "\r\n";
            echo 'Терминал: ' . $terminal->code . "\r\n";
            echo '- дата        ' . date('d.m.Y', strtotime($inp['date'])) . "\r\n";

            // Ищем операцию за выбранный день и кодом терминала
            $finance = (new Finance())->getOperation([
                'text'           => '%' . date('d.m.Y', strtotime($inp['date'])) . '%' . '; ' . '%' . $terminal->code . '%',
                'cat_id'         => self::CAT_BANK_OPERATION, // Банк
                'type'           => 'приход',
                'nodistribution' => true,
            ]);

            if (empty($finance)) {
                continue;
            }

            // Пользователи подразделения
            $arrUsersStore = (new User())->getStoreUsers([
                'store' => $terminal->store,
                'field' => 'ids',
            ])->pluck('id')->all();
            ;

            // Кто работал в этот день, на случай если доступ удален
            $arrUsersWork = (new UserDay())->getDayUsers([
                'store' => $terminal->store,
                'date'  => $inp['date']
            ])->pluck('user')->all();

            $arrUser = array_merge($arrUsersStore, $arrUsersWork);

            // Сумма оплат по банковским картам
            $sumPay = (new Pay())->sumPay([
                'status'  => 'Оплачено',
                'type'    => 'банковской картой',
                'setting' => $finance->setting,
                'moder'   => self::STATUS_ACTIVE,
                'all'     => true,
                'date'    => $inp['date'],
                'user'    => $arrUser,
            ]);

            // Сумма возвратов по банковским картам
            $sumReturn = (new Pay())->sumPay([
                'status'  => 'Возврат',
                'type'    => 'банковской картой',
                'setting' => $finance->setting,
                'moder'   => self::STATUS_ACTIVE,
                'all'     => true,
                'date'    => $inp['date'],
                'user'    => $arrUser,
            ]);

            // Сумма оплат (сверки) за вычетом возвратов
            $sum = $sumPay + $sumReturn;

            echo '- сумма оплат ' . date('d.m.Y', strtotime($inp['date'])) . "\r\n";

            // Сумма оплат по банку
            $regxp = '#' . 'сумма (.*).,' . '#isU';
            preg_match_all($regxp, $finance->text, $result);

            if (!empty($result)) {

                $sumBank = $result[1][0];

                $sumBank = str_replace(' ', '', $sumBank);
                $sumBank = (int) $sumBank;

            } else {

                // Комиссия
                $regxp = '#' . 'комиссия: (.*). возврат' . '#isU';
                preg_match_all($regxp, $finance->text, $result);

                if (!empty($result)) {
                    $commission = $result[1][0];
                }

                $commission = str_replace(' ', '', $commission);

                // Общая сумма поступления вместе с комиссией
                $sumBank = (int) ((float) $finance->sum + (float) $commission);

            }

            // Поступление участвовало в проверке
            (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $finance->id,
            ]);

            echo 'Оплата по банку: ' . $finance->id . "\r\n";
            echo '- сумма     ' . $finance->sum . "\r\n";
            echo '- дата      ' . date('d.m.Y', strtotime($inp['date'])) . "\r\n";
            echo '- оплаты    ' . $sumPay . "\r\n";
            echo '- возвраты  ' . $sumReturn . "\r\n";

            // Если суммы оплат по терминалу сходятся с поступившими в банк
            if ($sum == $sumBank) {
                continue;
            }

            // Подразделение
            $store = (new Store())->getStore(['id' => $terminal->store]);

            // Формируем задачу на проверку
            $task['title'] = 'Сверка итогов не сходится ' . $store->title . ' за ' . date('d.m', strtotime($inp['date']));
            $task['text']  = $store->title . ' за ' . date('d.m.y', strtotime($inp['date'])) . ' не сходится сумма д/c по банковским картам в банк поступило ' . $sumBank . ' р. (включая комиссию), по системе принято ' . $sum . ' р.';

            (new TaskService())->createTaskIfNotExists([
                'title' => $task['title'],
            ], [
                'user'       => 1,
                'cat'        => 'task',
                'type'       => 'Заказ',
                'title'      => $task['title'],
                'text'       => $task['text'],
                'moder_user' => self::USER_SYSTEM_ID,
            ]);

        }

        echo 'continue';

    }

    /**
     * Распределение оплат по заказам юр.лиц
     */
    public function distributeOrderPay($auth): void
    {

        // Заказы безнальные
        $orders = (new Order())->getOrders($auth, [
            'search' => [
                'status'   => ['Новый', 'Думает', 'Не дозвонились', 'Подтвержден', 'В обработке', 'В пути', 'Поступил'],
                'pay_type' => 'безнал',
                'get'      => true,
            ],
            'sort' => 'id DESC',
        ], true);

        foreach ($orders as $order) {

            echo "\r\n";
            echo 'Заказ: ' . $order->code . "\r\n";

            if (empty($order->company_inn)) {
                continue;
            }

            // Ищем операцию с оплатой данного заказа
            $finance = (new Finance())->getOperation([
                'text'           => '%' . $order->code . '%',
                'cat_id'         => self::CAT_BANK_OPERATION,
                'type'           => 'приход',
                'inn'            => $order->company_inn,
                'nodistribution' => true,
            ]);

            if (empty($finance)) {

                echo '- не найдена фин. операция по номеру заказа ' . $order->code . ', инн ' . $order->company_inn . "\r\n";

                // Ищем операцию с оплатой данного заказа
                $finance = (new Finance())->getOperation([
                    'cat_id'         => self::CAT_BANK_OPERATION, // Банк
                    'type'           => 'приход',
                    'inn'            => $order->company_inn,
                    'nodistribution' => true,
                ]);

            }

            if (empty($finance)) {
                continue;
            }

            // Проверяем наличие оплаты
            $payCheck = (new Pay())->getPay($auth, [
                'code'      => $order->code,
                'type'      => 'безнал',
                'paymentID' => $finance->num,
                'status'    => 'Оплачено',
            ]);

            if (!empty($payCheck)) {

                echo '- Оплата уже была добавлена' . "\r\n";
                continue;

            }

            // Если суммы оплаты меньше поступившей в банк
            if ($order->sum < $finance->sum) {

                echo '- суммы не сходятся ' . $order->sum . ' < ' . $finance->sum . "\r\n";

                // Формируем задачу магазину
                $inp          = [];
                $inp['title'] = 'Не распределена оплата по безналу';
                $inp['text']  = 'По заказу ' . $order->code . ' есть оплата от «' . $finance->title . '» на сумму ' . $finance->sum . ' руб. Я не смогла привязать оплату, так как сумма заказа меньше суммы оплаты. Обратитесь к руководству, если оплату необходимо привязать.';

                (new TaskService())->createTaskIfNotExists([
                    1 => [
                        'title' => $inp['title'],
                        'text'  => $inp['text'],
                        'date'  => date('Y-m-d'),
                    ],
                    0 => [
                        'title'         => $inp['title'],
                        'text'          => $inp['text'],
                        'accepted_null' => 'null'
                    ],
                ], [
                    'store'       => $order->store_report,
                    'cat'         => 'task',
                    'date'        => date('Y-m-d'),
                    'date_finish' => null,
                    'type'        => 'Заказ',
                    'title'       => $inp['title'],
                    'text'        => $inp['text'],
                    'code'        => $order->code,
                    'url'         => route('orders.index') . '?code=' . $order->code,
                    'url_name'    => 'Открыть заказ',
                    'moder_user'  => self::USER_SYSTEM_ID,
                ]);

                continue;

            }

            // Кто выставлял счет
            $operation = (new Operation())->getItem($auth, [
                'type' => 'order',
                'post' => $order->id,
                'text' => '%Распечатал Счет на оплату%',
            ]);

            // Если нашли, кто выставлял счет
            if (!empty($operation)) {

                // Подтверждаем заказ
                (new Order())->acceptFast($auth, [
                    'id'          => $order->id,
                    'code'        => $order->code,
                    'accepted_at' => date('Y-m-d'),
                    'moder_user'  => $operation->user,
                ]);

            } else {

                if ($order->moder == self::STATUS_NOACTIVE) {

                    // Формируем задачу магазину
                    $inp          = [];
                    $inp['title'] = 'Д/c поступили по заказу';
                    $inp['text']  = 'По заказу ' . $order->code . ' есть оплата от «' . $finance->title . '» на сумму ' . $finance->sum . ' руб. Я не смогла определить кто выставлял счет, поэтому подтвердите заказ самостоятельно.';

                    (new TaskService())->createTaskIfNotExists([
                        1 => [
                            'title' => $inp['title'],
                            'text'  => $inp['text'],
                            'date'  => date('Y-m-d'),
                        ],
                        0 => [
                            'title'         => $inp['title'],
                            'text'          => $inp['text'],
                            'accepted_null' => 'null'
                        ],
                    ], [
                        'store'      => $order->store_report,
                        'cat'        => 'task',
                        'date'       => date('Y-m-d'),
                        'type'       => 'Заказ',
                        'title'      => $inp['title'],
                        'text'       => $inp['text'],
                        'code'       => $order->code,
                        'url'        => route('orders.index') . '?code=' . $order->code,
                        'url_name'   => 'Открыть заказ',
                        'moder_user' => self::USER_SYSTEM_ID,
                    ]);

                }
            }

            // Добавляем оплату
            $payId = (new Pay())->insert($auth, [
                'orderId'        => $order->id,
                'type'           => 'безнал',
                'paymentID'      => $finance->num,
                'check'          => $finance->num,
                'status'         => 'Оплачено',
                'amount'         => $finance->sum,
                'amount_deposit' => $finance->sum,
                'moder'          => self::STATUS_ACTIVE,
                'moder_user'     => self::USER_SYSTEM_ID,
            ]);

            echo $payId;

            if (!is_numeric($payId)) {

                // Формируем задачу магазину
                $inp          = [];
                $inp['title'] = 'Не распределена оплата по безналу';
                $inp['text']  = 'По заказу ' . $order->code . ' вроде бы есть оплата от «' . $finance->title . '» на сумму ' . $finance->sum . ' руб. Я не смогла привязать оплату. Обратитесь к руководству, если оплату необходимо привязать.';

                (new TaskService())->createTaskIfNotExists([
                    1 => [
                        'title' => $inp['title'],
                        'text'  => $inp['text'],
                        'date'  => date('Y-m-d'),
                    ],
                    0 => [
                        'title'         => $inp['title'],
                        'text'          => $inp['text'],
                        'accepted_null' => 'null'
                    ],
                ], [
                    'store'      => $order->store_report,
                    'cat'        => 'task',
                    'date'       => date('Y-m-d'),
                    'type'       => 'Заказ',
                    'title'      => $inp['title'],
                    'text'       => $inp['text'],
                    'code'       => $order->code,
                    'url'        => route('orders.index') . '?code=' . $order->code,
                    'url_name'   => 'Открыть заказ',
                    'moder_user' => self::USER_SYSTEM_ID,
                ]);

                continue;

            }

            // Поступление участвовало в проверке
            (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $order->id,
            ]);

        }

        echo 'continue';

    }

    /**
     * Загрузка выписки по банку
     */
    public function loadBankStatement(array $inp)
    {

        // Создаем директорию
        if (!is_dir($this->path)) {
            mkdir($this->path);
        }

        // Создаем директорию
        if (!is_dir($this->path . $this->folder)) {
            mkdir($this->path . $this->folder);
        }

        // Загрузка файла
        $load = $inp['file']->move($this->path . $this->folder, $inp['file']->getClientOriginalName());

        if (!$load) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $load;

    }

    /**
     * Обработка выписки по банку
     */
    public function processingBankStatement()
    {

        // Добавляем уведомление
        (new Notice())->add([
            'date'    => null,
            'user'    => auth()->user()->id,
            'store'   => 0,
            'access'  => null,
            'title'   => 'CRON',
            'type'    => 'default',
            'message' => 'Обновление выписки по банку запущен. Процесс займет от 2 до 5 минут.',
            'url'     => null,
        ]);

        exec('/usr/bin/php /var/www/ai.trumart.ru/IMPORT/SBER/load.php');
        sleep(3);

        // Запускаем под системой
        $user = (new User())->getUser(['id' => 2]);

        $this->distributeSberTerminalPay($user);

        $this->distributeOrderPay($user);

        $this->distributeOrderCreditPay($user);

        $this->distributeContrPay($user);

        (new OrderBidsPay())->distributionAuto($user);

        (new Pay())->yooKassa($user);

        // Задача в очередь
        dispatch(new FinanceJob($user));

        return apiSuccess();

    }
}
