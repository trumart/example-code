<?php



namespace App\Services\Finance;

use App\Functions\Ftp;
use App\Models\Finance\Finance;
use App\Models\Finance\Setting;
use Illuminate\Support\Collection;

class SettingService extends BaseService
{

    /**
     * Список настроек для распределения финансовых операций
     *
     * @param array $inp
     * @return Collection
     */
    public function getSettings(array $inp): Collection
    {

        $settings = (new Setting())->getSettings($inp);

        foreach ($settings as $setting) {

            // Ищем компанию
            $setting->company = (new Finance())->getOperation([
                'inn' => $setting->inn
            ]);

        }

        return $settings;

    }

    /**
     * Распределение финансовых операций
     */
    public function distributionFinOperations(): void
    {

        $financeModel        = new Finance();
        $financeSettingModel = new Setting();

        // Все операции
        $finances = $financeModel->getOperations([
            'cat_id'         => self::CAT_BANK_OPERATION,
            'type'           => 'расход',
            'nodistribution' => true,
            'date_start'     => self::DATE_START_DISTRIBUTION,
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
                'distribution' => $financeModel->id,
            ]);

            // Выгрузка в 1С
            if (!$setting->unloading) {
                continue;
            }

            // Xml для EnterpriseData
            $xml = (new ExportService())->financeOperationExportXmlForEnterpriceData([
                'id' => $financeModel->id,
            ]);

            // Фин. оперция
            $finance = (new Finance())->getOperation([
                'id' => $financeModel->id,
            ]);

            // Имя файл
            $fileName = 'finance_' . $financeModel->id . '_v5.xml';

            // Записать в файл
            $fp = fopen($this->path . $fileName, 'w');
            fwrite($fp, $xml);
            fclose($fp);

            // Организация
            if ($finance->setting == 1) {

                // Загрузка файла на FTP
                $data = (new Ftp())->uploadFileForTRG([
                    'fileName' => $fileName,
                    'path'     => $this->path,
                ]);

            }

            echo $data;

        }

        echo 'TRUE';

    }
}
