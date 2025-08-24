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
    protected string $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Выплаты сотруднику
     *
     * @param FinanceRequest $request
     * @return AnonymousResourceCollection
     */
    public function pays(FinanceRequest $request): AnonymousResourceCollection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $inp = $request->validate([
            'userId' => ['required', 'numeric', 'exists:users,id'],
            'month'  => ['nullable', 'string'],
            'year'   => ['nullable', 'string'],
        ]);

        $finances = (new WorkerService())->getWorkerPay($inp);

        return FinanceResource::collection($finances);

    }
}
