<?php



namespace App\Services\Finance;

use App\Enums\CashReportType;
use App\Enums\PaymentType;
use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Notice;
use App\Models\Operation;
use App\Models\Pay;
use App\Models\StoreWork;
use App\Models\Tasks;
use App\Models\UserWork;
use App\Models\UserWorker;
use App\Services\Finance\EncashmentStrategy\EncashmentStrategyFactory;
use App\Services\Finance\RoleStrategy\RoleStrategyFactory;
use App\Services\Pay\PayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class FinanceService extends BaseService
{
    /**
     * Получает список финансовых операций
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @throws \Exception
     * @return Collection Коллекция операций
     */
    public function getOperations(array $inp): Collection
    {

        // Обработка модерации
        if (isset($inp['manager_status'])) {
            $inp['moder_manager_status'] = $inp['manager_status'];
        }

        if (isset($inp['store_status'])) {
            $inp['moder_store_status'] = $inp['store_status'];
        }

        if (isset($inp['date_type']) && $inp['date_type'] == 'date') {

            if (!empty($inp['date_start'])) {
                $inp['date_start'] = Carbon::parse($inp['date_start'])->format('Y-m-d');
            }

            if (!empty($inp['date_finish'])) {
                $inp['date_finish'] = Carbon::parse($inp['date_finish'])->format('Y-m-d');
            }

        } else {

            if (!empty($inp['date_start'])) {
                $inp['date_start'] = Carbon::parse($inp['date_start'])->startOfDay()->toDateTimeString();
            }

            if (!empty($inp['date_finish'])) {
                $inp['date_finish'] = Carbon::parse($inp['date_finish'])->endOfDay()->toDateTimeString();
            }

        }

        if (isset($inp['encashment']) && $inp['encashment'] == true) {
            $inp['type'][] = 'инкассация';
        }

        if (isset($inp['coming']) && $inp['coming'] == true) {
            $inp['type'][] = 'приход';
        }

        if (isset($inp['expense']) && $inp['expense'] == true) {
            $inp['type'][] = 'расход';
        }

        $strategy = RoleStrategyFactory::make();

        return $strategy->getOperations($inp);

    }

    /**
     * Получает сумму или список финансовых операций для кассового отчета
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @return array|float Коллекция операций или сумма
     */
    public function getCashReport(array $inp, CashReportType $type = CashReportType::LIST): Collection|float
    {

        return match ($type) {
            CashReportType::LIST => (new Finance())->getOperations($inp),
            CashReportType::SUM  => (new Finance())->sumOperations($inp),
        };

    }

    /**
     * Подсчет суммы в кассе подразделения
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @param PaymentType $type вид оплаты
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function calcSumInCashBoxStore(array $inp, PaymentType $type = PaymentType::CASH): Collection|JsonResponse
    {

        // Вид операций
        $type == 'наличные' ? $inp['paycash'] = 1 : $inp['paycash'] = 0;

        // Текущая смена
        $open = (new StoreWork())->getStoreWork([
            'store'     => $inp['store_id'],
            'date_like' => $inp['date']
        ]);

        if (empty($open)) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Не найдена открытая смена по подразделению'
            ], 422);

        }

        !isset($inp['date_start']) ?
            $inp['date_start'] = Carbon::parse($inp['date'])->startOfDay()->toDateTimeString() :
            $inp['date_start'] = Carbon::parse($inp['date_start'])->startOfDay()->toDateTimeString();

        !isset($inp['date_finish']) ?
            $inp['date_finish'] = Carbon::parse($inp['date'])->endOfDay()->toDateTimeString() :
            $inp['date_finish'] = Carbon::parse($inp['date_finish'])->endOfDay()->toDateTimeString();

        // СУММА
        // Предоплаты
        $total['pre'] = (new Pay())->getAllPay([
            'date_start'     => $inp['date_start'],
            'date_finish'    => $inp['date_finish'],
            'store'          => $inp['store_id'],
            'type'           => $type,
            'order_nostatus' => 'Закрыт'
        ], 'sum');

        // Оплаты
        $total['now'] = (new Pay())->getAllPay([
            'date_start'   => $inp['date_start'],
            'date_finish'  => $inp['date_finish'],
            'store'        => $inp['store_id'],
            'type'         => $type,
            'order_status' => 'Закрыт'
        ], 'sum');

        // Расходы
        $total['consumption'] = (new FinanceService())->getCashReport([
            'store_cash_id' => $inp['store_id'],
            'date_start'    => $inp['date_start'],
            'date_finish'   => $inp['date_finish'],
            'date_type'     => 'created_at',
            'paycash'       => $inp['paycash'],
            'type'          => 'расход'
        ], CashReportType::SUM);

        // Приходы
        $total['coming'] = (new FinanceService())->getCashReport([
            'store_cash_id' => $inp['store_id'],
            'date_start'    => $inp['date_start'],
            'date_finish'   => $inp['date_finish'],
            'date_type'     => 'created_at',
            'paycash'       => $inp['paycash'],
            'type'          => 'приход'
        ], CashReportType::SUM);

        // Инкассации
        $total['collection'] = (new FinanceService())->getCashReport([
            'store_cash_id' => $inp['store_id'],
            'date_start'    => $inp['date_start'],
            'date_finish'   => $inp['date_finish'],
            'date_type'     => 'created_at',
            'paycash'       => $inp['paycash'],
            'type'          => 'инкассация'
        ], CashReportType::SUM);

        // Сумма закрытия которая расчитывается автоматически
        if ($type == 'наличные') {

            // Сумма на закрытие
            $open->close_sum_auto = 0;
            $open->close_sum_auto += $open->open_sum ?? 0;
            $open->close_sum_auto += $total['pre'] + $total['now'] + ($total['coming'] - $total['consumption'] - $total['collection']);

            // Обновляем суммы закрытия, автоматический подсчет
            (new StoreWork())->updateCloseSumAuto([
                'id'             => $open->id,
                'close_sum_auto' => $open->close_sum_auto,
            ]);

        } elseif ($type == 'банковской картой') {

            // Сумма на закрытие
            $open->close_card_auto = $total['pre'] + $total['now'] + ($total['coming'] - $total['consumption'] - $total['collection']);

            // Обновляем суммы закрытия, автоматический подсчет
            (new StoreWork())->updateCloseSumAuto([
                'id'              => $open->id,
                'close_card_auto' => $open->close_card_auto,
            ]);
        }

        return $open;

    }

    /**
     * Кассовый отчет по рабочей смене
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function getCashboxFinance($inp): array
    {

        // Вид оплаты
        $inp['paycash'] = $inp['type'] === 'наличные' ? 1 : 0;

        $date       = Carbon::parse($inp['date']);
        $dateStart  = $date->startOfDay()->toDateTimeString();
        $dateFinish = $date->endOfDay()->toDateTimeString();

        // Данные на открытие
        $open = (new StoreWork())->getStoreWork([
            'store'     => $inp['store'],
            'date_like' => $date->format('Y-m-d')
        ]);

        // ОПЛАТЫ ПО ЗАКАЗАМ
        $finance = (new PayService())->getAllPay([
            'date'  => $date->format('Y-m-d'),
            'store' => $inp['store'],
            'type'  => $inp['type'],
        ]);

        // Финансовые операции
        $types = [
            'collection'  => 'инкассация',
            'consumption' => 'расход',
            'coming'      => 'приход',
        ];

        $baseFinanceParams = [
            'store_cash_id' => $inp['store'],
            'date_start'    => $dateStart,
            'date_finish'   => $dateFinish,
            'date_type'     => 'created_at',
            'paycash'       => $inp['paycash'],
        ];

        foreach ($types as $k => $type) {

            $finance['list'][$k] = $this->getCashReport(
                array_merge($baseFinanceParams, ['type' => $type]),
                CashReportType::LIST
            );

            $finance['sum'][$k] = $this->getCashReport(
                array_merge($baseFinanceParams, ['type' => $type]),
                CashReportType::SUM
            );

        }

        // Расчет суммы закрытия
        $closeSum = $finance['sum']['pre'] + $finance['sum']['pay'];
        $closeSum += ($finance['sum']['coming'] - $finance['sum']['consumption'] - $finance['sum']['collection']);

        // Сумма закрытия которая расчитывается автоматически
        if ($inp['type'] == 'наличные') {

            $open->close_sum_auto = ($open->open_sum ?? 0) + $closeSum;

            // Обновляем суммы закрытия, автоматический подсчет
            (new StoreWork())->updateCloseSumAuto([
                'id'             => $open->id,
                'close_sum_auto' => $open->close_sum_auto,
            ]);

        } elseif ($inp['type'] == 'банковской картой') {

            $open->close_card_auto = $closeSum;

            // Обновляем суммы закрытия, автоматический подсчет
            (new StoreWork())->updateCloseSumAuto([
                'id'              => $open->id,
                'close_card_auto' => $open->close_card_auto,
            ]);
        }

        return [
            'open'    => $open,
            'finance' => $finance,
        ];

    }

    /**
     * Согласование фин операции, универсально как для топ менеджмента так и для магазинов
     *
     * @param int $operationId id финансовой операции
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function accept(int $operationId): Finance|JsonResponse
    {

        $auth = auth()->user();

        // Проверяем открыта ли смена?
        $work = (new UserWork())->getUserWork([
            'user' => $auth->id,
            'date' => date('Y-m-d')
        ]);

        if (empty($work)) {
            return apiError('У тебя не открыта смена');
        }

        $financeModel = new Finance();

        // Операция
        $operation = $financeModel->getOperation(['id' => $operationId]);

        // Если менеджер
        if (!empty($auth->accesses['finance']->edit)) {

            // Если инкасс на расчетный счет
            if ($operation->type == 'инкассация' && $operation->user_pay == self::USER_SYSTEM_ID) {

                $data = [
                    'moder_manager'        => self::USER_SYSTEM_ID,
                    'moder_manager_status' => self::STATUS_ACTIVE,
                ];

            } else {

                // Есть ли касса у сотрудника
                $worker = (new UserWorker())->getUserWorker([
                    'user' => $auth->id,
                ]);

                if (!$worker->cash) {
                    return apiError('У тебя нет кассы, что бы согласовывать инкассации');
                }

                $data = [
                    'moder_manager'        => $auth->id,
                    'moder_manager_status' => self::STATUS_ACTIVE,
                ];

            }

        } else {

            $data = [
                'moder_store'        => $auth->id,
                'moder_store_status' => 1,
            ];

        }

        // Если операция уже одобрена менеджером
        if ($operation->moder_manager_status == 1) {

            $data = [
                'moder_store'        => $auth->id,
                'moder_store_status' => 1,
            ];

        }

        // Скрываем для сотрудников выплату Заработной платы после принятия
        if (in_array($operation->cat, [50,49,49,47])) {
            $data['view'] = 0;
        }

        $update = $financeModel->accept($operation->id, $data);

        if (!$update) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Добавляем операцию
        (new Operation())->insert([
            'user' => $auth->id,
            'type' => 'finance',
            'post' => $operation->id,
            'text' => 'Принял операцию ' . $operation->id . ' ' . $operation->title,
        ]);

        // Закрываем задачу
        (new Tasks())->close([
            'code'          => $operation->id,
            'accepted_user' => $auth->id,
            'title'         => ['Подтвердите фин. операцию'],
            'answer'        => 'Задача закрыта по операции "Фин. операция подтверждена" ' . $auth->name,
        ]);

        // Удаляем уведомления после приемки
        (new Notice())->remove([
            'title'   => 'Выплата д/c',
            'message' => '%выплата денежных средств%' . $operation->title . '%',
        ]);

        return apiSuccess($operation);

    }

    /**
     * Создание фин. операции из формы
     *
     * @param int $inp id финансовой операции
     * @return Finance|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function insert(array $inp): Finance|JsonResponse
    {

        $auth = auth()->user();

        // Открыта ли смена сотрудника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => $auth->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            return apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить добавить финансовую операцию');
        }

        // Дата
        $inp['date'] = Carbon::parse($inp['date'])->format('Y-m-d');

        $inp['store']      = $inp['store']      ?? $auth->store;
        $inp['store_cash'] = $inp['store_cash'] ?? $auth->store;

        if (empty($inp['store']) || empty($inp['store_cash'])) {
            return apiError('Нет данных по подразделению');
        }

        // Не видно магазинам
        if ($inp['view'] == 0) {

            $moder['store']        = self::STORE_ID_OFFICE;
            $moder['store_status'] = self::STATUS_ACTIVE;

            $moder['manager']        = $auth->id;
            $moder['manager_status'] = self::STATUS_ACTIVE;

        } else {

            if (in_array($auth->access, ['admin','zam'])) {

                // C согласованием
                if (isset($inp['check_store_accept']) && $inp['check_store_accept'] === true) {

                    $moder['store']        = $inp['store_cash'];
                    $moder['store_status'] = null;

                    $moder['manager']        = $auth->id;
                    $moder['manager_status'] = self::STATUS_ACTIVE;

                    // Без согласования
                } else {

                    $moder['store']        = $inp['store_cash'];
                    $moder['store_status'] = self::STATUS_ACTIVE;

                    $moder['manager']        = $auth->id;
                    $moder['manager_status'] = self::STATUS_ACTIVE;

                }

            } else {

                $moder['store']        = $inp['store_cash'];
                $moder['store_status'] = self::STATUS_ACTIVE;

                $moder['manager']        = null;
                $moder['manager_status'] = null;

            }
        }

        // Если не офис
        if ($inp['store_cash'] != self::STORE_ID_OFFICE) {

            // Закрыта ли смена подразделения
            $checkStore = (new StoreWork())->checkStoreWorkDate($inp['store_cash'], date('Y-m-d'));

            if (empty($checkStore)) {
                return apiError('Смена подразделения закрыта, нельзя добавить операцию');
            }

        }

        $insert = (new Finance())->insert([
            'date'                 => $inp['date'],
            'store_id'             => $inp['store'],
            'store_cash_id'        => $inp['store_cash'],
            'cashbox'              => $inp['cashbox'],
            'type'                 => $inp['type'],
            'paycash'              => $inp['paycash'],
            'cat_id'               => $inp['cat_id'],
            'title'                => $inp['title'],
            'text'                 => $inp['text'],
            'sum'                  => $inp['sum'],
            'view'                 => $inp['view'],
            'nds'                  => $inp['nds'],
            'nds_val'              => $inp['nds_val'],
            'user_id'              => $auth->id,
            'moder_store'          => $moder['store'],
            'moder_store_status'   => $moder['store_status'],
            'moder_manager'        => $moder['manager'],
            'moder_manager_status' => $moder['manager_status'],
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Фин. категория
        $cat = (new Cat())->getCat(['id' => $inp['cat_id']]);

        // Если создает магазин
        if (!empty($auth->store)) {

            // Добавляем уведомление
            (new Notice())->add([
                'date'    => null,
                'user'    => 0,
                'store'   => 0,
                'access'  => 'zam',
                'title'   => 'Фин. операция',
                'type'    => 'warning',
                'message' => 'Добавлена операция «' . $cat->title . '» на сумму ' . number_format($inp['sum'], 0, ',', ' ') . ' р. Необходимо подтверждение операции.',
                'url'     => route('finances.index'),
            ]);

        }

        return $insert;

    }

    public function edit(array $inp)
    {

        $data = (new Finance())->edit($inp);

        // Фин. операция
        $finance = Finance::findOrFail($inp['id']);

        if (!$finance) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $finance;

    }

    /**
     * Удаление финансовой операции мягкое
     *
     * @param int $operationId id финансовой операции
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function remove(int $financeId): JsonResponse
    {

        $financeModel = new Finance();
        $finance      = $financeModel->getOperation(['id' => $financeId]);

        // Касса магазина?
        if ($finance->store_cash_id != self::STORE_ID_OFFICE) {

            // Не закрыта ли смена магазина
            $checkStore = (new StoreWork())->checkStoreWorkDate($finance->store_cash_id, date('Y-m-d'));

            if (empty($checkStore)) {
                return apiError('Воу, Воу, Погоди, смена магазина уже закрыта, я не могу разрешить удалить операцию');
            }

        }

        if (date('Y-m-d', strtotime($finance->created_at)) != date('Y-m-d')) {
            return apiError('Извини но операцию можно удалить только день в день');
        }

        $delete = $financeModel->remove($finance->id);

        return apiSuccess($delete);

    }

    /**
     * Создание инкассации
     *
     * @param array $inp id финансовой операции
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function encashment(array $inp): JsonResponse
    {

        // Если есть привязка к подразделению
        $inp['store_cash'] ??= auth()->user()->store;

        // Открыта ли смена поздразделения, кто отдает?
        if (!empty($inp['store_cash'])) {

            $checkStore = (new StoreWork())->checkStoreWorkDate($inp['store_cash'], date('Y-m-d'));

            if (empty($checkStore)) {
                return apiError('Воу, Воу, Погоди смена подразделения закрыта, я не могу разрешить добавить операцию');
            }

        }

        // Открыта ли смена сотрудника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => auth()->user()->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            return apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить добавить операцию');
        }

        // Проверка даты
        if ($inp['date'] < date('Y-m-d')) {
            return apiError('Нельзя добавить инкассацию за предыдущие дни');
        }

        // Если инкассация от сотрудника к сотруднику - то заносим как Передача д/c
        if (!empty($inp['user_cash'])) {

            $data = EncashmentStrategyFactory::make('userToUser')->handle($inp);

        } elseif (substr_count($inp['user'], 'store_') > 0) {

            $data = EncashmentStrategyFactory::make('storeToStore')->handle(array_merge($inp, ['store' => str_replace('store_', '', $inp['user'])]));

        } else {

            $data = EncashmentStrategyFactory::make('storeToUser')->handle($inp);

        }

        return $data;

    }

    /**
     * Распределение оплаты по банку
     *
     * @param array $inp id, title, text, cat_id
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function distribute(array $inp): Collection|JsonResponse
    {

        // Текущая операция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        if (!empty($finance->distribution)) {
            return apiError('Данная операция уже была распределена ранее');
        }

        // Добавляем операцию
        $insert = (new Finance())->insert([
            'code'                 => $finance->code,
            'store_id'             => $finance->store,
            'store_cash_id'        => $finance->store_cash,
            'cashbox'              => $finance->cashbox,
            'type'                 => $finance->type,
            'paycash'              => $finance->paycash,
            'date'                 => $finance->date,
            'title'                => $inp['title'],
            'text'                 => $inp['text'],
            'cat_id'               => $inp['cat_id'],
            'num'                  => $finance->num,
            'inn'                  => $finance->inn,
            'sum'                  => $finance->sum,
            'user_id'              => $finance->user,
            'user_pay_id'          => $finance->user_pay,
            'view'                 => $finance->view,
            'distribution'         => $finance->id,
            'moder_store'          => $finance->moder_store,
            'moder_store_status'   => $finance->moder_store_status,
            'moder_manager'        => $finance->moder_manager,
            'moder_manager_status' => $finance->moder_manager_status,
        ]);

        if (!$insert) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Распределение
        $data = (new Finance())->distribution([
            'id'           => $finance->id,
            'distribution' => $insert->id,
        ]);

        return $data;

    }

    /**
     * Передача д/c
     *
     * @param array $inp id, title, text, cat_id
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function broadcast(array $inp): Collection|JsonResponse
    {

        // Открыта ли смена сотруника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => auth()->user()->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить данное действие');
        }

        // Сумма по платежам
        $paymentsSum = 0;

        // Список платежей
        foreach ($inp['finances'] as $item) {

            if (!isset($item['check']) || $item['check'] != true) {
                continue;
            }

            if ($item['check'] == true && !empty($item['distribution'])) {
                apiError('По платежу ' . $item['title'] . ' от ' . date('d.m.Y', strtotime($item['date'])) . ' на сумму ' . $item['sum'] . ' уже было распределение');
            }

            $paymentsSum += $item['sum'];

            // Название компании
            $companyTitle = $item['title'];

        }

        // Сумма передачи д/c больше чем сумма по платежам
        if ($inp['broadcast']['sum'] > $paymentsSum) {
            return apiError('Сумма передачи д/c больше чем сумма по выбранным платежам');
        }

        // Разница между суммой передачи и по платежам
        $diff = $paymentsSum - $inp['broadcast']['sum'];

        if (!empty($diff)) {

            // Добавляем приход
            (new Finance())->insert([
                'date'                 => date('Y-m-d'),
                'store_id'             => self::STORE_ID_OFFICE,
                'store_cash_id'        => self::STORE_ID_OFFICE,
                'cashbox'              => 2,
                'paycash'              => 0,
                'type'                 => 'приход',
                'title'                => 'Услуги',
                'cat_id'               => 26,
                'sum'                  => $diff,
                'view'                 => 0,
                'user_id'              => auth()->user()->id,
                'moder_store'          => self::STORE_ID_OFFICE,
                'moder_store_status'   => self::STATUS_ACTIVE,
                'moder_manager'        => auth()->user()->id,
                'moder_manager_status' => self::STATUS_ACTIVE,
            ]);

        }

        // Передача д/c
        $finance = (new Finance())->insert([
            'date'                 => date('Y-m-d'),
            'store_id'             => self::STORE_ID_OFFICE,
            'store_cash_id'        => self::STORE_ID_OFFICE,
            'cashbox'              => 1,
            'type'                 => 'расход',
            'paycash'              => 1,
            'cat_id'               => 40, // Передача д/c
            'title'                => 'Передача д/c ' . $companyTitle,
            'sum'                  => $paymentsSum,
            'view'                 => 0,
            'user_id'              => auth()->user()->id,
            'user_pay_id'          => 0,
            'moder_store'          => self::STORE_ID_OFFICE,
            'moder_store_status'   => self::STATUS_ACTIVE,
            'moder_manager'        => auth()->user()->id,
            'moder_manager_status' => self::STATUS_ACTIVE,
        ]);

        if (!$finance) {
            return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        foreach ($inp['finances'] as $item) {

            if (!isset($item['check']) || $item['check'] != true) {
                continue;
            }

            if ($item['check'] == true && !empty($item['distribution'])) {
                apiError('По платежу ' . $item['title'] . ' от ' . date('d.m.Y', strtotime($item['date'])) . ' на сумму ' . $item['sum'] . ' уже было распределение');
            }

            // Распределено
            $data = (new Finance())->distribution([
                'id'           => $item['id'],
                'distribution' => $finance,
            ]);

            if (!$data) {
                return apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
            }

        }

        return $finance;

    }
}
