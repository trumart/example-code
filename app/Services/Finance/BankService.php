<?php

namespace App\Services\Finance;

use App\Imports\BankImport;
use App\Jobs\Finance\BankJob;
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
use App\Services\Pay\PayService;
use App\Services\Task\TaskService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Webklex\IMAP\Facades\Client;

class BankService extends BaseService
{
    protected string $folder = 'bank'; // Папка для загрузки выписок банка

    /**
     * Распределение оплат сотрудникам зп по банку
     *
     * @return void
     */
    public function distributeUserWorkerWagePay(): void
    {

        $financeModel    = new Finance();
        $userModel       = new User();
        $userWorkerModel = new UserWorker();

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
            $worker = $userWorkerModel->getUserWorker([
                'inn' => $finance->inn,
            ]);

            if (empty($worker)) {
                continue;
            }

            // Пользователь
            $user = $userModel->getUser(['id' => $worker->user]);

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
     *
     * @return void
     */
    public function distributeContrPay(): void
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

            echo "\r\n";
            echo "- инн {$finance->inn} \r\n";
            echo "- платеж {$finance->num} \r\n";

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
            (new OrderBidsPay())->insert([
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
     *
     * @param $auth
     * @return void
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
            $payId = (new PayService())->insert([
                'order_id'       => $order->id,
                'payment_type'   => 'кредит',
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
            (new PayService())->accept([
                'id' => $payId
            ]);

        }

        echo 'continue';

    }

    /**
     * Распределение оплат по терминалу
     *
     * @param array $inp
     * @return void
     */
    public function distributeSberTerminalPay(array $inp = []): void
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
                'status'     => 'Оплачено',
                'type'       => 'банковской картой',
                'setting'    => $finance->setting,
                'no_message' => ['услуга доставки', 'услуга сборки / установки'],
                'moder'      => self::STATUS_ACTIVE,
                'all'        => true,
                'date'       => $inp['date'],
                'moder_user' => $arrUser,
            ]);

            // Сумма возвратов по банковским картам
            $sumReturn = (new Pay())->sumPay([
                'status'     => 'Возврат',
                'type'       => 'банковской картой',
                'setting'    => $finance->setting,
                'no_message' => ['услуга доставки', 'услуга сборки / установки'],
                'moder'      => self::STATUS_ACTIVE,
                'all'        => true,
                'date'       => $inp['date'],
                'moder_user' => $arrUser,
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
     *
     * @param $auth
     * @return void
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
            $payCheck = (new Pay())->getPay([
                'code'       => $order->code,
                'type'       => 'безнал',
                'payment_id' => $finance->num,
                'status'     => 'Оплачено',
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
            $payId = (new PayService())->insert([
                'order_id'       => $order->id,
                'payment_type'   => 'безнал',
                'payment_id'     => $finance->num,
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
     *
     * @param array $inp
     * @return JsonResponse
     */
    public function loadBankStatement(array $inp): JsonResponse
    {

        try {

            /** @var UploadedFile $file */
            $file = $inp['file'];

            // Сохраняем файл и получаем путь к файлу в storage/app
            $storedPath = $file->storeAs($this->folder, $file->getClientOriginalName());

            if (!$storedPath) {
                return response()->apiError('Не удалось сохранить файл.');
            }

            return response()->apiSuccess('Файл загружен в ' . $storedPath);

        } catch (\Exception $e) {

            Log::channel('error')->error('Ошибка загрузки банковской выписки', [
                'exception' => $e,
            ]);

            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');

        }

    }

    /**
     * Обработка файлов банковских выписок
     *
     * @return void
     */
    public function processingBankAllStatementSberExcel(): void
    {

        $files = Storage::files('bank');

        foreach ($files as $file) {

            $this->processingBankStatementSberExcel(Storage::path($file));

            // Удаляем файл
            Storage::delete($file);

        }

    }

    /**
     * Обработка файла выписки банка
     *
     * @param $file
     * @return void
     */
    public function processingBankStatementSberExcel($file): void
    {

        // Настройки колонок
        $arrColumn = [
            'Наименование счета'                             => 'setting_company',
            'Операции по счету: Дата операции'               => 'date',
            'Операции по счету: ИНН корреспондента'          => 'inn',
            'Операции по счету: КПП плательщика (102)'       => 'kpp',
            'Операции по счету: Наименование корреспондента' => 'title',
            'Операции по счету: Назначение платежа'          => 'text',
            'Операции по счету: Номер расчетного документа'  => 'num',
            'Операции по счету: Сумма платежа по дебету'     => 'sum_debet',
            'Операции по счету: Сумма платежа по кредиту'    => 'sum_credit',
        ];

        // EXCEL
        $rows = Excel::toArray(new BankImport(), $file);
        $rows = $rows[0] ?? [];

        if (empty($rows)) {
            return;
        }

        $arrColumn = array_map('trim', $arrColumn); // сразу уберём пробелы

        // Получаем карту колонок
        $header   = array_map('trim', $rows[0]);
        $colIndex = [];

        foreach ($header as $c => $col) {
            if ($col !== '' && isset($arrColumn[$col])) {
                $colIndex[$arrColumn[$col]] = $c;
            }
        }

        $global = [];

        // Перебор строк и вставка
        foreach ($rows as $k => $row) {

            if ($k === 0) {
                continue;
            } // пропускаем заголовки

            $bank = [
                'setting_company' => null,
                'code'            => null,
                'type'            => null,
                'text'            => null,
                'num'             => null,
                'sum'             => null,
                'title'           => null,
                'inn'             => null,
                'kpp'             => null,
                'sum_debet'       => null,
                'sum_credit'      => null,
                'date'            => null,
            ];

            foreach ($colIndex as $field => $index) {
                $bank[$field] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            // Организация
            if (!isset($global['setting_id']) && !empty($bank['setting_company'])) {
                $global['setting_id'] = $this->detectSettingIdByCompanyTitle($bank['setting_company']);
            }

            $bank['setting_id'] = $global['setting_id'];

            if (empty($bank['date']) || substr_count($bank['date'], 'Операции по счету') > 0) {
                continue;
            }

            // Дата платежа
            $bank['date'] = ExcelDate::excelToDateTimeObject($bank['date']);
            $bank['date'] = Carbon::instance($bank['date'])->format('Y-m-d');

            // Код платежа
            $bank['code'] = str_replace(['.', ':', ' ','-'], '', $bank['date']) . $bank['num'];

            // Расход
            if (!empty($bank['sum_debet'])) {

                $bank['type'] = 'расход';
                $bank['sum']  = $bank['sum_debet'];

            }

            // Приход
            if (!empty($bank['sum_credit'])) {

                $bank['type'] = 'приход';
                $bank['sum']  = $bank['sum_credit'];

            }

            if (empty($bank['inn'])) {
                continue;
            }

            if (empty($bank['sum'])) {
                continue;
            }

            echo "\r\n";
            echo $bank['title'] . "\r\n";
            echo '- организация ' . $bank['setting_id'] . "\r\n";
            echo '- номер ' . $bank['num'] . "\r\n";
            echo '- дата ' . $bank['date'] . "\r\n";
            echo '- инн ' . $bank['inn'] . "\r\n";
            echo '- вид ' . $bank['type'] . "\r\n";
            echo '- сумма ' . $bank['sum'] . ' р.' . "\r\n";

            // Проверяем операцию
            $check = (new Finance())->getOperation([
                'cat_id'     => self::CAT_BANK_OPERATION,
                'setting_id' => $bank['setting_id'],
                'user_id'    => self::USER_SYSTEM_ID,
                'date'       => $bank['date'],
                'num'        => $bank['num'],
                'inn'        => $bank['inn'],
                'sum'        => $bank['sum'],
            ]);

            if (!empty($check)) {
                echo '- операция найдена ' . "\r\n";
                continue;
            }

            $insert = (new Finance())->insert([
                'code'                 => $bank['code'],
                'setting_id'           => $bank['setting_id'],
                'store_id'             => self::STORE_ID_OFFICE,
                'store_cash_id'        => self::STORE_ID_OFFICE,
                'cashbox'              => 1,
                'type'                 => $bank['type'],
                'paycash'              => 0,
                'date'                 => $bank['date'],
                'title'                => $bank['title'],
                'text'                 => $bank['text'],
                'cat_id'               => self::CAT_BANK_OPERATION,
                'num'                  => $bank['num'],
                'inn'                  => $bank['inn'],
                'kpp'                  => $bank['kpp'],
                'sum'                  => $bank['sum'],
                'user_id'              => self::USER_SYSTEM_ID,
                'user_pay_id'          => 0,
                'view'                 => 0,
                'distribution'         => null,
                'source'               => 'банк',
                'moder_store_status'   => self::STATUS_ACTIVE,
                'moder_manager_status' => self::STATUS_ACTIVE,
                'moder_store'          => null,
                'moder_manager'        => null,
            ]);

            if (!$insert) {
                echo '- ошибка, не добавлена операция в базу ' . "\r\n";
            } else {
                echo '- добавлена операция в базу ' . "\r\n";
            }

        }

        echo 'continue';

    }

    /**
     * Создаем очереди задач по обработке банковской выписки, распределение оплат и т.п
     *
     * @return JsonResponse
     */
    public function processingBankStatement(): JsonResponse
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

        // Задача в очередь на обработку выписки
        dispatch(new BankJob());

        // Задача в очередь на распределение фин операций по настройкам
        dispatch(new FinanceJob());

        return response()->apiSuccess();

    }

    /**
     * Определение организации в банковской выписки
     *
     * @param string $settingCompanyTitle
     * @return int
     */
    private function detectSettingIdByCompanyTitle(string $settingCompanyTitle): int
    {

        if (
            $settingCompanyTitle == 'ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ТРУМАРТ РЕТЕЙЛ ГРУПП"' || $settingCompanyTitle == 'ООО "ТРГ"'
        ) {
            return 1;
        } else {
            return 2;
        }

    }

    /**
     * Выгрузка выписки Сбер из почты
     *
     * @return JsonResponse
     */
    public function downloadSberBankStatementFromEmail()
    {

        // Работа с почтой trumart
        $client = Client::account('info_trumart');
        $client->connect();

        // Получаем папку входящих
        $mailbox = $client->getFolder('INBOX');

        // Получаем письма от отправителя и с нужной темой
        $mails = $mailbox->query()
            ->from('SberBusiness@sberbank.ru')
            ->subject('Выписка по счету')
            ->limit(10)
            ->get();

        foreach ($mails as $mail) {

            // Проверяем, прочитано ли письмо
            if ($mail->getFlags()->has('\Seen')) {
                continue;
            }

            // Получаем тело письма (обычно HTML или plain text)
            $body = $mail->getHTMLBody();

            if (!$body) {
                continue;
            }

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true); // Чтобы не было предупреждений из-за невалидного HTML
            $dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // Ищем ссылки, текст которых содержит нужную фразу
            $nodes = $xpath->query("//a[contains(text(), 'Скачать выписку: ONEC, EXCEL.')]");

            foreach ($nodes as $node) {

                $url = $node->getAttribute('href');

                if (!empty($url)) {
                    break;
                }
            }

            $filename = 'zip/sber_' . date('Y_m_d') . '.zip'; // путь внутри storage/app

            $response = Http::get($url);

            if (!$response->successful()) {
                return response()->apiError('Ошибка при скачивании файла: ' . $response->status());
            }

            $put = Storage::put($filename, $response->body());

            if (!$put) {

            }

            $zipPath     = storage_path('app/' . $filename); // путь к архиву
            $extractPath = storage_path('app/unpacked/'); // куда распаковать

            $zip = new \ZipArchive();

            $unpack = $zip->open($zipPath);

            if (!$unpack) {
                return response()->apiError('Не удалось распаковать архив');
            }

            // Распаковать архив в указанную папку
            $zip->extractTo($extractPath);
            $zip->close();
            $files = scandir($extractPath);

            $targetFolder = $this->folder;

            foreach ($files as $file) {

                if ($file === '.' || $file === '..') {
                    continue;
                }

                $fullPath = $extractPath . $file;

                // Проверяем, что это файл и расширение Excel
                if (is_file($fullPath) && preg_match('/\.(xls|xlsx)$/i', $file)) {

                    // Сохраняем файл в storage/app/excel_files
                    Storage::putFileAs($targetFolder, new \Illuminate\Http\File($fullPath), $file);

                }

                // Можно удалить оригинал, если нужно
                unlink($fullPath);

            }

        }

        return response()->apiSuccess();
    }
}
