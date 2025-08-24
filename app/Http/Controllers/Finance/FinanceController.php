<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Http\Resources\Finance\FinanceResource;
use App\Models\Finance\Finance;
use App\Models\Store;
use App\Models\User;
use App\Models\UserWorker;
use App\Services\Finance\FinanceService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class FinanceController extends Controller
{
    protected string $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Страница финансовых операций
     *
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application
     */
    public function page(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {

        // Проверка доступа
        (new User())->checkAccess('page', $this->auth, $this->section, 'view');

        // Онлайн
        (new User())->online($this->auth);

        // Точки продаж
        $stores = (new Store())->getStores([
            'type'  => ['пункт выдачи', 'магазин', 'офис'],
            'moder' => 1,
        ]);

        // Кому можно отдавать инкас?
        $workers = (new UserWorker())->getUsersWorker($this->auth, [
            'post'  => ['Генеральный директор', 'Заместитель генерального директора', 'Водитель', 'Водитель и Сборщик'],
            'moder' => 1
        ]);

        return view('page.finance', [
            'auth'    => $this->auth,
            'stores'  => $stores,
            'workers' => $workers,
        ]);

    }

    /**
     * Список операций
     *
     * @param FinanceRequest $request
     * @throws \Exception
     * @return AnonymousResourceCollection
     */
    public function finances(FinanceRequest $request): AnonymousResourceCollection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $inp = $request->validate([
            'setting_id'   => ['nullable', 'numeric'],
            'store_id'     => ['nullable', 'numeric'],
            'cat_id'       => ['nullable', 'numeric'],
            'user_pay_id'  => ['nullable', 'numeric'],
            'date_start'   => ['nullable', 'date'],
            'date_finish'  => ['nullable', 'date'],
            'date_type'    => ['nullable', 'string'],
            'inn'          => ['nullable', 'numeric'],
            'view'         => ['nullable', 'numeric'],
            'text'         => ['nullable', 'string'],
            'coming'       => ['nullable', 'boolean'],
            'encashment'   => ['nullable', 'boolean'],
            'expense'      => ['nullable', 'boolean'],
            'store_status' => ['nullable', 'numeric'],
        ]);

        // Финансовые операции
        $finances = (new FinanceService())->getOperations($inp);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    /**
     * Список операций ожидающих согласования менеджера
     *
     * @throws \Exception
     * @return AnonymousResourceCollection
     */
    public function financesManagerWaitAccept(): AnonymousResourceCollection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Финансовые операции
        $finances = (new FinanceService())->getOperations([
            'store_status'   => 1,
            'manager_status' => 0,
        ]);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    /**
     * Список операций ожидающих согласования менеджера
     *
     * @throws \Exception
     * @return AnonymousResourceCollection
     */
    public function financesShopWaitAccept(): AnonymousResourceCollection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Финансовые операции
        $finances = (new FinanceService())->getOperations([
            'store_status' => 0,
        ]);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    // Операции
    public function operations()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $search = $this->request->input('search', []);

        $items = (new Finance())->getOperations($search);

        return json_encode($items);

    }

    /**
     * Кассовый отчет
     *
     * @param FinanceRequest $request
     * @return array|JsonResponse
     */
    public function cashbox(FinanceRequest $request): array|JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $inp = $request->validate([
            'type'  => ['required', 'string'],
            'store' => ['required', 'numeric'],
            'date'  => ['required', 'date'],
        ]);

        return (new FinanceService())->getCashboxFinance([
            'store' => $inp['store'],
            'type'  => $inp['type'],
            'date'  => $inp['date']
        ]);

    }

    /**
     * Добавляем операцию
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function insert(FinanceRequest $request): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online(auth()->user());

        $inp = $request->validate([
            'date'               => ['required', 'date'],
            'store_id'           => ['nullable', 'numeric'],
            'store_cash_id'      => ['nullable', 'numeric'],
            'title'              => ['required', 'string'],
            'text'               => ['nullable', 'string'],
            'sum'                => ['required', 'numeric'],
            'view'               => ['nullable', 'numeric'],
            'check_store_accept' => ['nullable', 'boolean'],
            'nds'                => ['nullable', 'numeric'],
            'nds_val'            => ['nullable', 'numeric'],
            'type'               => ['required', 'string'],
            'cat_id'             => ['required', 'numeric'],
            'cashbox'            => ['nullable', 'numeric'],
            'paycash'            => ['nullable', 'numeric'],
            'company_title'      => ['nullable', 'string'],
            'inn'                => ['nullable', 'numeric'],
            'doc_num'            => ['nullable', 'string'],
            'doc_date'           => ['nullable', 'date'],
            'doc_type'           => ['nullable', 'string'],
            'items'              => ['nullable', 'array'],
        ]);

        // Устанавливаем значение по умолчанию
        $inp['view']    = $inp['view']    ?? 1;
        $inp['type']    = $inp['type']    ?? 'расход';
        $inp['cashbox'] = $inp['cashbox'] ?? 2;

        return (new FinanceService())->insert($inp);

    }

    /**
     * Копируем фин операцию
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function copy(FinanceRequest $request): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online(auth()->user());

        $inp = $request->validate([
            'id'       => ['required', 'numeric'],
            'cat_id'   => ['required', 'numeric'],
            'store_id' => ['nullable', 'numeric'],
            'date'     => ['required', 'date'],
            'sum'      => ['required', 'numeric'],
            'doc_num'  => ['nullable', 'string'],
            'doc_date' => ['nullable', 'date'],
            'doc_type' => ['nullable', 'string'],
            'title'    => ['required', 'string'],
            'text'     => ['nullable', 'string'],
            'items'    => ['nullable', 'array'],
        ]);

        return (new FinanceService())->copy($inp);

    }

    /**
     * Создание инкассации
     *
     * @param FinanceRequest $request
     * @return JsonResponse|Collection
     */
    public function encashment(FinanceRequest $request): JsonResponse|Collection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'user'       => ['required'],
            'user_cash'  => 'nullable',
            'store_cash' => 'nullable',
            'paycash'    => 'nullable',
            'sum'        => ['required', 'string'],
        ]);

        return (new FinanceService())->encashment($inp);

    }

    /**
     * Согласование операции
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function accept(FinanceRequest $request): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        return (new FinanceService())->accept($inp['id']);

    }

    /**
     * Удаление
     *
     * @param int $financeId
     * @return JsonResponse
     */
    public function remove(int $financeId): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        // Онлайн
        (new User())->online($this->auth);

        return (new FinanceService())->remove($financeId);

    }

    /**
     * Редактирование финансовой операции
     *
     * @param FinanceRequest $request
     * @return Finance|JsonResponse
     */
    public function edit(FinanceRequest $request): Finance|JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'id'            => ['required', 'numeric'],
            'cat_id'        => ['required', 'numeric'],
            'store_id'      => ['required', 'numeric'],
            'date'          => ['required', 'date'],
            'sum'           => ['required', 'numeric'],
            'company_title' => ['nullable', 'string'],
            'inn'           => ['nullable', 'numeric'],
            'doc_num'       => ['nullable', 'numeric'],
            'doc_date'      => ['nullable', 'date'],
            'doc_type'      => ['nullable', 'string'],
            'title'         => ['required', 'string'],
            'text'          => ['nullable', 'string'],
        ]);

        return (new FinanceService())->edit($inp);

    }

    /**
     * Ручное распределение финансовой операции
     *
     * @param FinanceRequest $request
     * @return FinanceResource
     */
    public function distribute(FinanceRequest $request): FinanceResource
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Онлайн
        (new User())->online($this->auth);

        $inp = $request->validate([
            'id'            => ['required', 'numeric'],
            'store_id'      => ['required', 'numeric'],
            'date'          => ['required', 'date'],
            'cat_id'        => ['required', 'numeric'],
            'company_title' => ['nullable', 'string'],
            'inn'           => ['nullable', 'numeric'],
            'doc_num'       => ['nullable', 'string'],
            'doc_date'      => ['nullable', 'date'],
            'doc_type'      => ['nullable', 'string'],
            'title'         => ['required', 'string'],
            'text'          => ['nullable', 'string'],
        ]);

        $data = (new FinanceService())->distribute($inp);

        return new FinanceResource($data);

    }

    /**
     * Передача денежных средств
     *
     * @param FinanceRequest $request
     * @return FinanceResource
     */
    public function broadcast(FinanceRequest $request): FinanceResource
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Онлайн
        (new User())->online($this->auth);

        $inp = $request->validate([
            'broadcast'     => ['required','array'],
            'broadcast.sum' => ['required','numeric'],
            'finances'      => ['required','array'],
        ]);

        $data = (new FinanceService())->broadcast($inp);

        return new FinanceResource($data);

    }
}
