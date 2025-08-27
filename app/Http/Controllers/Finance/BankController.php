<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Services\Finance\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

        $this->middleware('user.online');

    }

    /**
     * Загрузка выписки по банку
     *
     * @param FinanceRequest $request
     * @param BankService $bankService
     * @return JsonResponse
     */
    public function load(FinanceRequest $request, BankService $bankService): JsonResponse
    {

        $inp = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        return $bankService->loadBankStatement($inp);

    }

    /**
     * Запуск обработки файлов банковских выписок
     *
     * @param BankService $bankService
     * @return JsonResponse
     */
    public function processing(BankService $bankService): JsonResponse
    {

        $bankService->processingBankAllStatementSberExcel();
        $bankService->processingBankStatement();

        return response()->apiSuccess(null, 'Обработка завершена');

    }
}
