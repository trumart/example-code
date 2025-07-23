<?php



namespace App\Services\Finance;

use App\Exports\FinanceExport;
use App\Functions\Ftp;
use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Functions;
use App\Models\Setting;
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

        $items = (new Finance())->getOperation([
            'date_type'  => 'created_at',
            'date_start' => '2025-01-01 00:00:00',
        ]);

        $arr = [];

        foreach ($items as $item) {

            // Категория
            $cat = $financeCatModel->getCat(['id' => $item->cat]);

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
                $catParent->id    ?? null,
                $catParent->title ?? null,
                $cat->id          ?? null,
                $cat->title       ?? null,
                $item->inn,
                $item->created_at,
            ];

        }

        $fileName = '';

        Excel::store(new FinanceExport($arr), $fileName);

        // Загрузка файла на FTP
        $data = (new Ftp())->uploadFileFor1C([
            'fileName' => $this->fileNameXlsToFtp,
            'path'     => $this->path,
        ]);

        return $data;

    }

    /**
     * Генерации финансовой операции в XML для 1C EnterpriceData
     */
    public function financeOperationExportXmlForEnterpriceData(array $inp = [])
    {

        // Финансовая операция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        // Данные о компании
        $setting = (new Setting())->getSetting($finance->setting_id);

        // Генерация uid
        if (empty($finance->uid)) {
            $finance->uid = (new Functions())->uuid();
        }

        // Обновляем идентификтор
        (new Finance())->updateUid([
            'id'  => $finance->id,
            'uid' => $finance->uid,
        ]);

        return view('components.finance.export.xml', compact('finance', 'setting'))->render();

    }
}
