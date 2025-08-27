<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Http\Resources\Finance\FinanceResource;
use App\Models\User;
use App\Services\Finance\WorkerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkerController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Выплаты сотруднику
     *
     * @param FinanceRequest $request
     * @param WorkerService $workerService
     * @return AnonymousResourceCollection
     */
    public function pays(FinanceRequest $request, WorkerService $workerService): AnonymousResourceCollection
    {

        $inp = $request->validate([
            'userId' => ['required', 'numeric', 'exists:users,id'],
            'month'  => ['nullable', 'string'],
            'year'   => ['nullable', 'string'],
        ]);

        $finances = $workerService->getWorkerPay($inp);

        return FinanceResource::collection($finances);

    }
}
