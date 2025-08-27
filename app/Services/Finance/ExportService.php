<?php

namespace App\Services\Finance;

use App\Exports\FinanceExport;
use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Functions;
use App\Models\Setting;
use App\Services\Ftp\FtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportService extends BaseService
{
    protected $fileNameXlsToFtp = 'finance.xlsx';

    /**
     * Выгрузка финансовых операций Excel на FTP для системы аналитики
     */
    public function financeOperationsExportXlsToFtp()
    {

        $financeCatModel = new Cat();

        $items = (new Finance())->getOperations([
            'date_type'  => 'created_at',
            'date_start' => '2025-01-01 00:00:00',
        ]);

        $arr = [];

        foreach ($items as $item) {

            // Категория
            $cat = $financeCatModel->getCat(['id' => $item->cat_id]);

            // Родительская категория
            !empty($cat->parent_id) ? $catParent = $financeCatModel->getCat(['id' => $cat->parent_id]) : $catParent = null;

            $arr[] = [
                $item->id,
                $item->setting_id,
                $item->store_id,
                $item->store_cash_id,
                $item->type,
                $item->date,
                $item->title,
                $item->text,
                $item->sum,
                $item->source,
                $catParent->id    ?? null,
                $catParent->title ?? null,
                $cat->id          ?? null,
                $cat->title       ?? null,
                $item->inn,
                $item->user_id,
                $item->user_pay_id,
                $item->moder_manager,
                $item->inn,
                $item->created_at,
            ];

        }

        Excel::store(new FinanceExport($arr), $this->fileNameXlsToFtp);

        // Загрузка файла на FTP
        $data = (new FtpService())->uploadFileFor1C([
            'fileName' => $this->fileNameXlsToFtp,
            'path'     => $this->path,
        ]);

        return $data;

    }

    /**
     * Генерации финансовой операции в XML для 1C EnterpriceData
     */
    public function financeOperationExportXmlForEnterpriseData(int $financeId)
    {

        // Финансовая операция
        $finance = (new Finance())->getOperation(['id' => $financeId]);

        // Данные о компании
        $setting = (new Setting())->getSetting($finance->setting_id);

        // Генерация uid
        if (empty($finance->uid)) {
            $finance->uid = (new Functions())->uuid();
        }

        // Обновляем идентификтор
        $update = (new Finance())->updateUid([
            'id'  => $finance->id,
            'uid' => $finance->uid,
        ]);

        if (!$update) {
            return response()->apiError('Ошибка генерации Uid операции');
        }

        return view('components.finance.export.xml', compact('finance', 'setting'))->render();

    }

    /**
     * Загрузка файла на FTP для 1C EnterpriceData
     */
    public function financeOperationExportXmlForEnterpriseDataToFtp(int $financeId): JsonResponse
    {

        // Xml для EnterpriseData
        $xml = $this->financeOperationExportXmlForEnterpriseData($financeId);

        // Фин. операция
        $finance = (new Finance())->getOperation(['id' => $financeId]);

        // Имя файл
        $fileName = 'finance_' . $financeId . '_v5.xml';

        // Сохраняем файл
        $save = Storage::disk('local')->put('exports/' . $fileName, $xml);

        if (!$save) {
            return response()->apiError('Не получилось сохранить файл');
        }

        $path = Storage::disk('local')->path('exports/');

        // Организация
        if ($finance->setting_id == 1) {

            // Загрузка файла на FTP
            $data = (new FtpService())->uploadFileForTRG([
                'fileName' => $fileName,
                'path'     => $path,
            ]);

            if (!$data) {
                response()->apiError('Ошибка загрузки файла на FTP');
            }

            return response()->apiSuccess();

        }

        return response()->apiError('Выгрузка не настроена по данной Организации');

    }
}
