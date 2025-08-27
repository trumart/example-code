<?php

namespace App\Services\Finance;

use App\Models\Finance\Finance;
use App\Models\Finance\Setting;
use App\Services\Traits\CacheTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService extends BaseService
{
    use CacheTrait;

    private string $cacheTag = 'finance_setting';

    /**
     * Список настроек для распределения финансовых операций
     *
     * @param array $inp
     * @return Collection
     */
    public function getSettings(array $inp = []): Collection
    {

        $key = $this->makeCacheKey($inp);

        return Cache::tags([$this->cacheTag])
            ->remember($key, 600, fn () => (new Setting())->getSettings($inp));

    }

    /**
     * Распределение финансовых операций
     *
     * @param array $inp
     * @return void
     */
    public function distributionFinOperations(array $inp = []): void
    {

        $financeModel        = new Finance();
        $financeSettingModel = new Setting();

        $inp['date_start'] = $inp['date_start'] ?? self::DATE_START_DISTRIBUTION;

        // Все операции
        $finances = $financeModel->getOperations([
            'cat_id'         => self::CAT_BANK_OPERATION,
            'type'           => 'расход',
            'inn'            => $inp['inn'] ?? null,
            'nodistribution' => true,
            'date_start'     => $inp['date_start'],
            'date_type'      => 'date'
        ]);

        foreach ($finances as $finance) {

            if (empty($finance->inn)) {
                continue;
            }

            // Настройка
            $setting = $financeSettingModel->getSetting(['inn' => $finance->inn]);

            if (empty($setting)) {
                continue;
            }

            $insert = $financeModel->insert([
                'code'                 => $finance->code,
                'setting_id'           => $finance->setting_id,
                'store_id'             => $finance->store_id,
                'store_cash_id'        => $finance->store_cash_id,
                'cashbox'              => $finance->cashbox,
                'type'                 => $finance->type,
                'paycash'              => $finance->paycash,
                'date'                 => $finance->date,
                'title'                => $finance->title,
                'text'                 => $finance->text,
                'cat_id'               => $setting->cat_id,
                'num'                  => $finance->num,
                'inn'                  => $finance->inn,
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
                continue;
            }

            // Распределение
            $data = (new Finance())->distribution([
                'id'           => $finance->id,
                'distribution' => $insert->id,
            ]);

            if (!$data) {
                continue;
            }

            // Создаем черновик
            if ((bool) $setting->doc_close === true) {

                $this->createDraftDocClose($finance->id);
                continue;

            }

            // Выгрузка в 1С
            if ((bool) $setting->unloading === true) {

                $this->exportCloseDocFor1C($finance);

            }

        }

    }

    /**
     * Обработка фин операций для выгрузки в 1С или создания черновика закрывающих документов
     *
     * @param array $inp
     * @return void
     */
    public function createDraftDocCloseOrExport1C(array $inp): void
    {

        $financeModel        = new Finance();
        $financeSettingModel = new Setting();

        $inp['date_start'] = $inp['date_start'] ?? self::DATE_START_DISTRIBUTION;

        // Все операции
        $finances = $financeModel->getOperations([
            'no_cat_id'  => self::CAT_BANK_OPERATION,
            'type'       => 'расход',
            'inn'        => $inp['inn'] ?? null,
            'date_start' => $inp['date_start'],
            'date_type'  => 'date'
        ]);

        foreach ($finances as $finance) {

            if (empty($finance->distribution) || empty($finance->inn)) {
                continue;
            }

            // Настройка
            $setting = $financeSettingModel->getSetting(['inn' => $finance->inn]);

            if (empty($setting)) {
                continue;
            }

            // Создаем черновик
            if ((bool) $setting->doc_close === true) {

                $this->createDraftDocClose($finance->id);
                continue;

            }

            // Выгрузка в 1С
            if ((bool) $setting->unloading === true) {

                $this->exportCloseDocFor1C($finance);

            }

        }

        echo 'TRUE';

    }

    /**
     * Формируем черновик для фиксации закрывающих документов
     *
     * @param array $inp
     * @return JsonResponse
     */
    private function createDraftDocClose(array $inp): JsonResponse
    {

        $update = (new Finance())->edit([
            'id'  => $inp['id'],
            'upd' => 0,
        ]);

        if (!$update) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess();

    }

    /**
     * Выгрузка в 1С
     *
     * @param Finance $finance
     * @return void
     */
    private function exportCloseDocFor1C(Finance $finance): void
    {

        // Xml для EnterpriseData
        (new ExportService())->financeOperationExportXmlForEnterpriseDataToFtp($finance->id);

    }

    /**
     * Добавляем настройку распределения фин операций
     *
     * @param array $inp
     * @return Setting|JsonResponse
     */
    public function edit(array $inp): Setting|JsonResponse
    {

        $this->checkIncomingData($inp);

        $data = (new Setting())->edit($inp);

        if (!$data) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $data;

    }

    /**
     * Добавляем настройку распределения фин операций
     *
     * @param array $inp
     * @return Setting|JsonResponse
     */
    public function insert(array $inp): Setting|JsonResponse
    {

        $this->checkIncomingData($inp);

        $data = (new Setting())->insert($inp);

        if (!$data) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $data;

    }

    /**
     * Удаляем настройку распределения фин операций
     *
     * @param int $id
     * @return JsonResponse
     */
    public function remove(int $id): JsonResponse
    {

        $data = (new Setting())->remove($id);

        if (!$data) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess();

    }

    /**
     * Проверка входящих данных
     *
     * @param array $inp
     * @return void
     */
    public function checkIncomingData(array $inp): void
    {

        if ($inp['unloading'] === true && $inp['doc_close'] === true) {

            throw new HttpResponseException(
                response()->apiError('Нельзя одновременно выбрать и автоматическую выгрузку в 1С и формирование черновиков документов для фиксации')
            );

        }

    }
}
