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
    private string $cacheTag = 'finance';

    /**
     * Получает список финансовых операций
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @throws \Exception
     * @return Collection Коллекция операций
     */
    public function getOperations(array $inp): Collection
    {

        // Маппинг статусов
        $statusMap = [
            'manager_status' => 'moder_manager_status',
            'store_status'   => 'moder_store_status',
        ];

        foreach ($statusMap as $inputKey => $outputKey) {
            if (isset($inp[$inputKey])) {
                $inp[$outputKey] = $inp[$inputKey];
            }
        }

        // Обработка дат
        if (!empty($inp['date_start'])) {
            $date              = Carbon::parse($inp['date_start']);
            $inp['date_start'] = ($inp['date_type'] ?? null) === 'date'
                ? $date->format('Y-m-d')
                : $date->startOfDay()->toDateTimeString();
        }

        if (!empty($inp['date_finish'])) {
            $date               = Carbon::parse($inp['date_finish']);
            $inp['date_finish'] = ($inp['date_type'] ?? null) === 'date'
                ? $date->format('Y-m-d')
                : $date->endOfDay()->toDateTimeString();
        }

        // Добавление типов
        $typeMap = [
            'encashment' => 'инкассация',
            'coming'     => 'приход',
            'expense'    => 'расход',
        ];

        foreach ($typeMap as $flag => $value) {
            if (!empty($inp[$flag])) {
                $inp['type'][] = $value;
            }
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
    public function calcSumInCashBoxStore(array $inp, PaymentType $type = PaymentType::CASH): StoreWork|JsonResponse
    {

        // Вид операций
        $type == 'наличные' ? $inp['paycash'] = 1 : $inp['paycash'] = 0;

        // Текущая смена
        $open = (new StoreWork())->getStoreWork([
            'store'     => $inp['store_id'],
            'date_like' => Carbon::parse($inp['date_start'])->format('Y-m-d'),
        ]);

        if (empty($open)) {
            return response()->apiError('Не найдена открытая смена по подразделению');
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
            'date_start'      => Carbon::parse($inp['date_start'])->startOfDay()->toDateTimeString(),
            'date_finish'     => Carbon::parse($inp['date_finish'])->endOfDay()->toDateTimeString(),
            'store'           => $inp['store_id'],
            'type'            => $type,
            'order_no_status' => 'Закрыт'
        ], CashReportType::SUM);

        // Оплаты
        $total['now'] = (new Pay())->getAllPay([
            'date_start'   => Carbon::parse($inp['date_start'])->startOfDay()->toDateTimeString(),
            'date_finish'  => Carbon::parse($inp['date_finish'])->endOfDay()->toDateTimeString(),
            'store'        => $inp['store_id'],
            'type'         => $type,
            'order_status' => 'Закрыт'
        ], CashReportType::SUM);

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

        } else {

            return response()->apiError('Неизвестный тип оплаты');

        }

        return $open;

    }

    /**
     * Кассовый отчет по рабочей смене
     *
     * @param array $inp Массив с параметрами: moder, date_start, encashment и пр.
     * @return array|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function getCashboxFinance($inp): array|JsonResponse
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

        if (empty($open)) {
            return response()->apiError('Смена подразделения закрыта');
        }

        // ОПЛАТЫ ПО ЗАКАЗАМ
        $finance = (new PayService())->getAllPay([
            'date_start'  => $dateStart,
            'date_finish' => $dateFinish,
            'store'       => $inp['store'],
            'type'        => $inp['type'],
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
            return response()->apiError('У тебя не открыта смена');
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
                    return response()->apiError('У тебя нет кассы, что бы согласовывать инкассации');
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
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
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

        return response()->apiSuccess($operation);

    }

    /**
     * Создание фин. операции из формы
     *
     * @param int $inp id финансовой операции
     * @return JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function insert(array $inp): JsonResponse
    {

        $auth = auth()->user();

        // Открыта ли смена сотрудника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => $auth->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            return response()->apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить добавить финансовую операцию');
        }

        // Дата
        $inp['date'] = Carbon::parse($inp['date'])->format('Y-m-d');

        $inp['store_id']      = $inp['store_id']      ?? $auth->store;
        $inp['store_cash_id'] = $inp['store_cash_id'] ?? $auth->store;

        if (empty($inp['store_id']) || empty($inp['store_cash_id'])) {
            return response()->apiError('Нет данных по подразделению');
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

                    $moder['store']        = $inp['store_cash_id'];
                    $moder['store_status'] = null;

                    $moder['manager']        = $auth->id;
                    $moder['manager_status'] = self::STATUS_ACTIVE;

                    // Без согласования
                } else {

                    $moder['store']        = $inp['store_cash_id'];
                    $moder['store_status'] = self::STATUS_ACTIVE;

                    $moder['manager']        = $auth->id;
                    $moder['manager_status'] = self::STATUS_ACTIVE;

                }

            } else {

                $moder['store']        = $inp['store_cash_id'];
                $moder['store_status'] = self::STATUS_ACTIVE;

                $moder['manager']        = null;
                $moder['manager_status'] = null;

            }
        }

        // Если не офис
        if ($inp['store_cash_id'] != self::STORE_ID_OFFICE) {

            // Закрыта ли смена подразделения
            $checkStore = (new StoreWork())->checkStoreWorkDate($inp['store_cash_id'], date('Y-m-d'));

            if (empty($checkStore)) {
                return response()->apiError('Смена подразделения закрыта, нельзя добавить операцию');
            }
        }

        // Обработка ИНН
        $inp['inn'] = $this->getInn($inp);

        $insert = (new Finance())->insert([
            'date'                 => $inp['date'],
            'store_id'             => $inp['store_id'],
            'store_cash_id'        => $inp['store_cash_id'],
            'cashbox'              => $inp['cashbox'],
            'type'                 => $inp['type'],
            'paycash'              => $inp['paycash'],
            'cat_id'               => $inp['cat_id'],
            'title'                => $inp['title'],
            'text'                 => $inp['text'],
            'sum'                  => $inp['sum'],
            'view'                 => $inp['view'],
            'inn'                  => $inp['inn']      ?? null,
            'doc_num'              => $inp['doc_num']  ?? null,
            'doc_date'             => $inp['doc_date'] ?? null,
            'doc_type'             => $inp['doc_type'] ?? null,
            'nds'                  => $inp['nds']      ?? null,
            'nds_val'              => $inp['nds_val']  ?? null,
            'user_id'              => $auth->id,
            'moder_store'          => $moder['store'],
            'moder_store_status'   => $moder['store_status'],
            'moder_manager'        => $moder['manager'],
            'moder_manager_status' => $moder['manager_status'],
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
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

        // Содержание фин операции
        if (empty($inp['items'])) {
            return response()->apiSuccess($insert);
        }

        foreach ($inp['items'] as $item) {

            (new ChildService())->insert([
                'parent_id' => $insert->id,
                'code'      => $item['code'],
                'title'     => $item['title'],
                'quantity'  => $item['quantity'],
                'price'     => $item['price'],
            ]);

        }

        return response()->apiSuccess($insert);

    }

    /**
     * Создание фин. операции из формы
     *
     * @param int $inp id финансовой операции
     * @return Finance|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function copy(array $inp): Finance|JsonResponse
    {

        if ($inp['cat_id'] == self::CAT_BANK_OPERATION) {
            return response()->apiError('Нельзя скопировать фин. операцию в категории Банк');
        }

        $operation = (new Finance())->getOperation([
            'id' => $inp['id'],
        ]);

        $insert = (new Finance())->insert([
            'date'                 => $inp['date'],
            'store_id'             => $inp['store_id'],
            'store_cash_id'        => $operation->store_cash_id,
            'cashbox'              => $operation->cashbox,
            'type'                 => $operation->type,
            'paycash'              => $operation->paycash,
            'cat_id'               => $inp['cat_id'],
            'title'                => $inp['title'],
            'text'                 => $inp['text'],
            'sum'                  => $inp['sum'],
            'view'                 => $operation->view,
            'doc_num'              => $inp['doc_num']     ?? null,
            'doc_date'             => $inp['doc_date']    ?? null,
            'doc_type'             => $inp['doc_type']    ?? null,
            'inn'                  => $operation->inn     ?? null,
            'nds'                  => $operation->nds     ?? null,
            'nds_val'              => $operation->nds_val ?? null,
            'user_id'              => auth()->user()->id,
            'moder_store'          => $operation->moder_store,
            'moder_store_status'   => $operation->moder_store_status,
            'moder_manager'        => $operation->moder_manager,
            'moder_manager_status' => $operation->moder_manager_status,
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($insert);

    }

    /**
     * Редактирование фин операции
     * @param array $inp
     * @return JsonResponse
     */
    public function edit(array $inp): JsonResponse
    {

        // Фин. операция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        if ($finance->cat_id === self::CAT_BANK_OPERATION) {
            return response()->apiError('Извини, но банковская операция не подлежит редактированию');
        }

        // Обработка ИНН
        $inp['inn'] = $this->getInn($inp);

        // Нет в базе поля
        if (isset($inp['company_title'])) {
            unset($inp['company_title']);
        }

        $data = (new Finance())->edit($inp);

        if (!$data) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Фин. операция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        if (!$finance) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($finance);

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
                return response()->apiError('Воу, Воу, Погоди, смена магазина уже закрыта, я не могу разрешить удалить операцию');
            }

        }

        // Если наличные
        if (!empty($finance->paycash)) {

            if (date('Y-m-d', strtotime($finance->created_at)) != date('Y-m-d') && auth()->user()->access != 'admin') {
                return response()->apiError('Извини но операцию можно удалить только день в день');
            }

        }

        $delete = $financeModel->remove($finance->id);

        if (!$delete) {
            return response()->apiError('Не получилось удалить фин. операцию');
        }

        // Были ли связь? - убираем ее
        if (!empty($finance->distribution)) {

            (new Finance())->distribution([
                'id'           => $finance->distribution,
                'distribution' => null,
            ]);

        }

        return response()->apiSuccess($delete);

    }

    /**
     * Создание инкассации
     *
     * @param array $inp id финансовой операции
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function encashment(array $inp): Collection|JsonResponse
    {

        // Дата
        $inp['date'] = Carbon::now()->format('Y-m-d');

        // Оставляем только цифры и точку
        $inp['sum'] = preg_replace('/[^\d,\.]/', '', $inp['sum']);
        $inp['sum'] = str_replace(',', '.', $inp['sum']);

        // Если есть привязка к подразделению
        $inp['store_cash'] ??= auth()->user()->store;

        // Открыта ли смена поздразделения, кто отдает?
        if (!empty($inp['store_cash'])) {

            $checkStore = (new StoreWork())->checkStoreWorkDate($inp['store_cash'], date('Y-m-d'));

            if (empty($checkStore)) {
                return response()->apiError('Воу, Воу, Погоди смена подразделения закрыта, я не могу разрешить добавить операцию');
            }

        }

        // Открыта ли смена сотрудника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => auth()->user()->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            return response()->apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить добавить операцию');
        }

        // Проверка даты
        if ($inp['date'] < date('Y-m-d')) {
            return response()->apiError('Нельзя добавить инкассацию за предыдущие дни');
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
    public function distribute(array $inp): Finance|JsonResponse
    {

        // Текущая операция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        if (!empty($finance->distribution)) {
            return response()->apiError('Данная операция уже была распределена ранее');
        }

        // Обработка ИНН
        $inp['inn'] = $this->getInn($inp);

        // Добавляем операцию
        $insert = (new Finance())->insert([
            'code'                 => $finance->code,
            'store_id'             => $inp['store_id'] ?? $finance->store_id,
            'store_cash_id'        => $finance->store_cash_id,
            'cashbox'              => $finance->cashbox,
            'type'                 => $finance->type,
            'paycash'              => $finance->paycash,
            'date'                 => $finance->date,
            'title'                => $inp['title'],
            'text'                 => $inp['text'],
            'cat_id'               => $inp['cat_id'],
            'num'                  => $finance->num,
            'inn'                  => $inp['inn']      ?? null,
            'doc_num'              => $inp['doc_num']  ?? null,
            'doc_date'             => $inp['doc_date'] ?? null,
            'doc_type'             => $inp['doc_type'] ?? null,
            'sum'                  => $finance->sum,
            'user_id'              => $finance->user_id,
            'user_pay_id'          => $finance->user_pay_id,
            'view'                 => $finance->view,
            'distribution'         => $finance->id,
            'moder_store'          => $finance->moder_store,
            'moder_store_status'   => $finance->moder_store_status,
            'moder_manager'        => $finance->moder_manager,
            'moder_manager_status' => $finance->moder_manager_status,
        ]);

        if (!$insert) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Распределение
        $finance = (new Finance())->distribution([
            'id'           => $finance->id,
            'distribution' => $insert->id,
        ]);

        return $finance;

    }

    /**
     * Передача д/c
     *
     * @param array $inp id, title, text, cat_id
     * @return Collection|JsonResponse Коллекция данных по рабочей смене подразделения или ошибка
     */
    public function broadcast(array $inp): Finance|JsonResponse
    {

        // Открыта ли смена сотруника
        $checkUser = (new UserWork())->checkUserWorkDate(['user' => auth()->user()->id, 'date' => date('Y-m-d')]);

        if (empty($checkUser)) {
            return response()->apiError('Воу, Воу, Погоди твоя смена закрыта, я не могу разрешить данное действие');
        }

        // Сумма по платежам
        $paymentsSum = 0;

        // Список платежей
        foreach ($inp['finances']['data'] as $item) {

            if (!isset($item['check']) || $item['check'] != true) {
                continue;
            }

            if ($item['check'] == true && !empty($item['distribution'])) {
                return response()->apiError('По платежу ' . $item['title'] . ' от ' . date('d.m.Y', strtotime($item['date'])) . ' на сумму ' . $item['sum'] . ' уже было распределение');
            }

            $paymentsSum += $item['sum'];

            // Название компании
            $companyTitle = $item['title'];

        }

        // Сумма передачи д/c больше чем сумма по платежам
        if ($inp['broadcast']['sum'] > $paymentsSum) {
            return response()->apiError("Сумма передачи д/c {$inp['broadcast']['sum']} больше чем сумма {$paymentsSum} по выбранным платежам");
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
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        // Список платежей
        foreach ($inp['finances']['data'] as $item) {

            if (!isset($item['check']) || $item['check'] != true) {
                continue;
            }

            if ($item['check'] == true && !empty($item['distribution'])) {
                return response()->apiError('По платежу ' . $item['title'] . ' от ' . date('d.m.Y', strtotime($item['date'])) . ' на сумму ' . $item['sum'] . ' уже было распределение');
            }

            // Распределено
            $data = (new Finance())->distribution([
                'id'           => $item['id'],
                'distribution' => $finance->id,
            ]);

            if (!$data) {
                return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
            }

        }

        return $finance;

    }

    /**
     * Получение ИНН компании в зависимости от переданных данных ИНН или Название компании
     */
    public function getInn(array $inp): int|null|JsonResponse
    {

        if (!empty($inp['inn']) && is_numeric($inp['inn'])) {

            $innLen = mb_strlen($inp['inn']);

            if ($innLen !== 10 && $innLen !== 12) {
                return response()->apiError('ИНН должно содержать или 10 или 12 цифр');
            }

            return (int) $inp['inn'];

        }

        // Если передали ИНН вместо названия компании
        if (isset($inp['company_title']) && is_numeric($inp['company_title'])) {

            $innLen = mb_strlen($inp['company_title']);

            if ($innLen !== 10 && $innLen !== 12) {
                return response()->apiError('ИНН должно содержать или 10 или 12 цифр');
            }

            return (int) $inp['company_title'];

        }

        return null;

    }
}
