<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SettingRequest;
use App\Http\Resources\Finance\SettingResource;
use App\Jobs\Finance\FinanceJob;
use App\Jobs\Finance\SettingJob;
use App\Models\Finance\Setting;
use App\Models\User;
use App\Services\Finance\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SettingController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Список настроек по распределению финансовых операций
     *
     * @param SettingRequest $request
     * @return AnonymousResourceCollection
     */
    public function settings(SettingRequest $request): AnonymousResourceCollection
    {

        $inp = $request->validated();

        // Финансовые операции
        $data = (new SettingService())->getSettings($inp);

        return SettingResource::collection($data);

    }

    /**
     * Добавляем настройку
     *
     * @param SettingRequest $request
     * @return Setting|JsonResponse
     */
    public function insert(SettingRequest $request): Setting|JsonResponse
    {

        $inp = $request->validate([
            'cat_id'    => ['required', 'numeric'],
            'inn'       => ['required', 'numeric'],
            'unloading' => ['nullable', 'boolean'],
            'doc_close' => ['nullable', 'boolean'],
            'nds'       => ['nullable', 'boolean'],
            'nds_val'   => ['nullable','numeric'],
        ]);

        return (new SettingService())->insert($inp);

    }

    /**
     * Редактируем настройку
     *
     * @param SettingRequest $request
     * @return Setting|JsonResponse
     */
    public function edit(SettingRequest $request): Setting|JsonResponse
    {

        $inp = $request->validate([
            'id'        => ['required', 'numeric'],
            'cat_id'    => ['required', 'numeric'],
            'inn'       => ['required', 'numeric'],
            'unloading' => ['nullable', 'boolean'],
            'doc_close' => ['nullable', 'boolean'],
            'nds'       => ['nullable', 'boolean'],
            'nds_val'   => ['nullable','numeric'],
        ]);

        return (new SettingService())->edit($inp);

    }

    /**
     * Удаление операций
     *
     * @param int $settingId
     * @return JsonResponse
     */
    public function remove(int $settingId): JsonResponse
    {

        return (new SettingService())->remove($settingId);

    }

    /**
     * Запуск процедуры распределения финансовых операций на основании настроек
     *
     * @param SettingRequest $request
     * @return JsonResponse
     */
    public function distribute(SettingRequest $request): JsonResponse
    {

        $inp = $request->validate([
            'date_start' => ['required', 'date'],
            'inn'        => ['required', 'numeric'],
        ]);

        // Задача в очередь на распределение фин операций по настройкам
        dispatch(new FinanceJob($inp['inn'], $inp['date_start']));

        // Задача в очередь на выгрузку в 1С или создания черновиков закрывающих документов
        dispatch(new SettingJob($inp['inn'], $inp['date_start']));

        return response()->apiSuccess();

    }
}
