<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceCopyRequest;
use App\Http\Requests\Finance\FinanceInsertRequest;
use App\Http\Requests\Finance\FinanceRequest;
use App\Http\Resources\Finance\FinanceResource;
use App\Models\Finance\Finance;
use App\Models\Store;
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

    public function __construct(Request $request)
    {

        parent::__construct($request);

        $this->middleware('user.online');

    }

    /**
     * Страница финансовых операций
     *
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application
     */
    public function page(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {

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
     * @param FinanceService $financeService
     * @return AnonymousResourceCollection
     * @throws \Exception
     */
    public function finances(FinanceRequest $request, FinanceService $financeService): AnonymousResourceCollection
    {

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
        $finances = $financeService->getOperations($inp);

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
     * @param FinanceService $financeService
     * @return AnonymousResourceCollection
     * @throws \Exception
     */
    public function financesManagerWaitAccept(FinanceService $financeService): AnonymousResourceCollection
    {

        $finances = $financeService->getOperations([
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
     * @param FinanceService $financeService
     * @return AnonymousResourceCollection
     * @throws \Exception
     */
    public function financesShopWaitAccept(FinanceService $financeService): AnonymousResourceCollection
    {

        $finances = $financeService->getOperations([
            'store_status' => 0,
        ]);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    /**
     * Кассовый отчет
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return array|JsonResponse
     */
    public function cashbox(FinanceRequest $request, FinanceService $financeService): array|JsonResponse
    {

        $inp = $request->validate([
            'type'  => ['required', 'string'],
            'store' => ['required', 'numeric'],
            'date'  => ['required', 'date'],
        ]);

        return $financeService->getCashboxFinance([
            'store' => $inp['store'],
            'type'  => $inp['type'],
            'date'  => $inp['date']
        ]);

    }

    /**
     * Добавляем операцию
     *
     * @param FinanceInsertRequest $request
     * @param FinanceService $financeService
     * @return JsonResponse
     */
    public function insert(FinanceInsertRequest $request, FinanceService $financeService): JsonResponse
    {

        return $financeService->insert($request->validated());

    }

    /**
     * Копируем фин операцию
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return JsonResponse
     */
    public function copy(FinanceCopyRequest $request, FinanceService $financeService): JsonResponse
    {

        return $financeService->copy($request->validated());

    }

    /**
     * Создание инкассации
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return JsonResponse|Collection
     */
    public function encashment(FinanceRequest $request, FinanceService $financeService): JsonResponse|Collection
    {

        $inp = $request->validate([
            'user'       => ['required'],
            'user_cash'  => 'nullable',
            'store_cash' => 'nullable',
            'paycash'    => 'nullable',
            'sum'        => ['required', 'string'],
        ]);

        return $financeService->encashment($inp);

    }

    /**
     * Согласование операции
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return JsonResponse
     */
    public function accept(FinanceRequest $request, FinanceService $financeService): JsonResponse
    {

        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        return $financeService->accept($inp['id']);

    }

    /**
     * Удаление
     *
     * @param int $financeId
     * @param FinanceService $financeService
     * @return JsonResponse
     */
    public function remove(int $financeId, FinanceService $financeService): JsonResponse
    {

        return $financeService->remove($financeId);

    }

    /**
     * Редактирование финансовой операции
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return Finance|JsonResponse
     */
    public function edit(FinanceRequest $request, FinanceService $financeService): Finance|JsonResponse
    {

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

        return $financeService->edit($inp);

    }

    /**
     * Ручное распределение финансовой операции
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return FinanceResource
     */
    public function distribute(FinanceRequest $request, FinanceService $financeService): FinanceResource
    {

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

        $data = $financeService->distribute($inp);

        return new FinanceResource($data);

    }

    /**
     * Передача денежных средств
     *
     * @param FinanceRequest $request
     * @param FinanceService $financeService
     * @return FinanceResource
     */
    public function broadcast(FinanceRequest $request, FinanceService $financeService): FinanceResource
    {

        $inp = $request->validate([
            'broadcast'     => ['required','array'],
            'broadcast.sum' => ['required','numeric'],
            'finances'      => ['required','array'],
        ]);

        $data = $financeService->broadcast($inp);

        return new FinanceResource($data);

    }
}
