<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Models\User;
use App\Services\Finance\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    protected string $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Загрузка выписки по банку
     *
     * @param FinanceRequest $request
     * @return JsonResponse
     */
    public function load(FinanceRequest $request): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        $inp = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        return (new BankService())->loadBankStatement($inp);

    }

    /**
     * Запуск обработки файлов банковских выписок
     *
     * @return JsonResponse
     */
    public function processing(): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $bankService = new BankService();

        $bankService->processingBankAllStatementSberExcel();
        $bankService->processingBankStatement();

        return response()->apiSuccess(null, 'Обработка завершена');

    }
}
