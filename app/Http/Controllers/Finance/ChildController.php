<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Http\Resources\Finance\FinanceResource;
use App\Services\Finance\ChildService;
use App\Services\Finance\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChildController extends Controller
{
    protected string $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Содержание финансовой операции
     *
     * @param FinanceRequest $request
     * @throws \Exception
     * @return AnonymousResourceCollection
     */
    public function items(FinanceRequest $request): AnonymousResourceCollection
    {

        $inp = $request->validate([
            'parent_id'    => ['required', 'integer', 'exists:finance,id'],
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
     * Добавление
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function insert(FinanceRequest $request): JsonResponse
    {

        $inp = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:finance,id'],
            'code'      => ['required', 'string'],
            'title'     => ['required', 'string'],
            'quantity'  => ['required', 'string'],
            'price'     => ['required', 'numeric'],
        ]);

        return (new ChildService())->insert($inp);

    }

    /**
     * Редактирование
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function edit(FinanceRequest $request): JsonResponse
    {

        $inp = $request->validate([
            'id'    => ['required', 'integer', 'exists:finance,id'],
            'code'  => ['required', 'string'],
            'title' => ['required', 'string'],
            'text'  => ['required', 'string'],
            'sum'   => ['required', 'numeric'],
        ]);

        return (new FinanceService())->edit($inp);

    }
}
