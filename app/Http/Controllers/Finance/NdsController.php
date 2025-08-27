<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\NdsService;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NdsController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Страница отчета по НДС
     *
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application
     */
    public function page(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {

        return view('page.finance_nds', [
            'auth' => $this->auth,
        ]);

    }

    /**
     * Отчет по НДС
     *
     * @return JsonResponse
     */
    public function nds(): JsonResponse
    {

        $inp['date_start']  = $this->request->input('date_start', Carbon::now()->subDays(30)->format('Y-m-d'));
        $inp['date_finish'] = $this->request->input('date_finish', date('Y-m-d'));

        // Отчет по НДС
        $data = (new NdsService())->getReport($inp);

        return response()->apiSuccess($data);

    }
}
