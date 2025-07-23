<?php



namespace App\Http\Controllers\Finance;

use App\Functions\Ftp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest;
use App\Http\Resources\Finance\FinanceResource;
use App\Models\Finance\Finance;
use App\Models\Store;
use App\Models\User;
use App\Models\UserWorker;
use App\Services\Finance\BankService;
use App\Services\Finance\ExportService;
use App\Services\Finance\FinanceService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected $section = 'finance';

    protected $path = '/var/www/ai.trumart.ru/IMPORT/1C/files/';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    // Операции по финансам
    public function page()
    {

        // Проверка доступа
        (new User())->checkAccess('page', $this->auth, $this->section, 'view');

        // Онлайн
        (new User())->online($this->auth);

        // Точки продаж
        $stores = (new Store())->getStores([
            'type'  => ['пункт выдачи', 'магазин', 'офис'],
            'moder' => 1,
        ]);

        // Кому можно отдавать инкас?
        $workers = (new UserWorker())->getUsersWorker($this->auth, [
            'post'  => ['Генеральный директор', 'Заместитель генерального директора', 'Водитель', 'Водитель и Сборщик'],
            'moder' => 1
        ]);

        return view('page.finance', [
            'auth'    => $this->auth,
            'stores'  => $stores,
            'workers' => $workers,
        ]);

    }

    // Список операций
    public function finances(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $inp = $request->validate([
            'setting_id'   => ['nullable', 'numeric'],
            'store_id'     => ['nullable', 'numeric'],
            'cat_id'       => ['nullable', 'numeric'],
            'user_pay_id'  => ['nullable', 'numeric'],
            'date_start'   => ['nullable', 'date'],
            'date_finish'  => ['nullable', 'date'],
            'date_type'    => ['nullable', 'string'],
            'inn'          => ['nullable', 'numeric'],
            'view'         => ['nullable', 'numeric'],
            'text'         => ['nullable', 'string'],
            'coming'       => ['nullable', 'boolean'],
            'encashment'   => ['nullable', 'boolean'],
            'expense'      => ['nullable', 'boolean'],
            'store_status' => ['nullable', 'numeric'],
        ]);

        // Финансовые операции
        $finances = (new FinanceService())->getOperations($inp);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    // Список операций ожидающих согласования менеджера
    public function financesManagerWaitAccept()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Финансовые операции
        $finances = (new FinanceService())->getOperations([
            'store_status'   => 1,
            'manager_status' => 0,
        ]);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    // Список операций ожидающих согласования менеджера
    public function financesShopWaitAccept()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Финансовые операции
        $finances = (new FinanceService())->getOperations([
            'store_status' => 0,
        ]);

        $collection = FinanceResource::collection($finances);

        return $collection->additional([
            'total' => [
                'sum' => (int) $finances->sum('sum'),
            ]
        ]);

    }

    // Операции
    public function operations()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $search = $this->request->input('search', []);

        $items = (new Finance())->getOperations($search);

        return json_encode($items);

    }

    // Операции по кассе
    public function cashbox(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $inp = $request->validate([
            'type'  => ['required', 'string'],
            'store' => ['nullable', 'numeric'],
            'date'  => ['required', 'date'],
        ]);

        // Кассо сотрудника
        if (empty($inp['store']) && !empty($this->auth->store)) {
            $inp['store'] = auth()->user()->store;
        }

        $items = (new FinanceService())->getCashboxFinance([
            'store' => $inp['store'],
            'type'  => $inp['type'],
            'date'  => $inp['date']
        ]);

        return json_encode($items);

    }

    // Загрузка выписки
    public function load(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        $inp = $request->validate([
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'],
        ]);

        $data = (new BankService())->loadBankStatement($inp);

        return $data;

    }

    // Обработка выписка банка
    public function processing()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $data = (new BankService())->processingBankStatement();

        return $data;

    }

    // Добавляем операцию
    public function store(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online(auth()->user());

        $inp = $request->validate([
            'date'               => ['required', 'date'],
            'store'              => ['nullable', 'numeric'],
            'store_cash'         => ['nullable', 'numeric'],
            'title'              => ['required', 'string'],
            'text'               => ['nullable', 'string'],
            'sum'                => ['required', 'numeric'],
            'view'               => ['nullable', 'numeric'],
            'check_store_accept' => ['nullable', 'boolean'],
            'nds'                => ['nullable', 'numeric'],
            'nds_val'            => ['nullable', 'numeric'],
            'type'               => ['required', 'string'],
            'cat_id'             => ['required', 'numeric'],
            'cashbox'            => ['nullable', 'numeric'],
            'paycash'            => ['nullable', 'numeric'],
        ]);

        // Устанавливаем значение по умолчанию
        $inp['view']    = $inp['view']    ?? 1;
        $inp['type']    = $inp['type']    ?? 'расход';
        $inp['cashbox'] = $inp['cashbox'] ?? 2;

        $data = (new FinanceService())->insert($inp);

        return new FinanceResource($data);

    }

    // Добавляем инкасацию
    public function encashment(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'user'       => ['required'],
            'user_cash'  => 'nullable',
            'store_cash' => 'nullable',
            'paycash'    => 'nullable',
            'sum'        => ['required', 'string'],
        ]);

        // Дата
        $inp['date'] = date('Y-m-d');

        // Сумма только числа
        $inp['sum'] = str_replace([' ','  '], '', $inp['sum']);
        $inp['sum'] = str_replace(',', '.', $inp['sum']);

        $data = (new FinanceService())->encashment($inp);

        return $data;

    }

    // Принял
    public function accept(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = (new FinanceService())->accept($inp['id']);

        return $data;

    }

    // Удаление
    public function remove(int $financeId)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        // Онлайн
        (new User())->online($this->auth);

        $data = (new FinanceService())->remove($financeId);

        return $data;

    }

    // Редактирование
    public function edit(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'id'    => 'nullable',
            'cat'   => 'nullable',
            'store' => 'nullable',
            'title' => 'nullable',
            'text'  => 'nullable',
        ]);

        $data = (new Finance())->edit($inp);

        return $data;

    }

    // Распределение оплаты
    public function distribute(FinanceRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Онлайн
        (new User())->online($this->auth);

        $inp = $request->validate([
            'id'     => ['required', 'numeric'],
            'cat_id' => ['required', 'numeric'],
            'title'  => ['required', 'string'],
            'text'   => ['nullable', 'string'],
        ]);

        $data = (new FinanceService())->distribute($inp);

        return new FinanceResource($data);

    }

    // Передача д/c
    public function broadcast(FinanceRequest $request)
    {

        // Проверка авторизации
        if (!$this->auth) {
            return view('components.error.default', ['error' => 'Ошибка доступа - Авторизуйтесь в системе.']);
        }

        // Проверка доступа
        if (!$this->auth->accesses[$this->section]->edit) {
            return view('components.error.default', ['error' => 'Недостаточно прав доступа']);
        }

        // Онлайн
        (new User())->online($this->auth);

        $inp = $request->validate([
            'broadcast'     => ['required','array'],
            'broadcast.sum' => ['required','numeric'],
            'finances'      => ['required','array'],
        ]);

        $data = (new FinanceService())->broadcast($inp);

        return new FinanceResource($data);

    }

    // Выгрузка в EnterpriseData
    public function xmlEnterpriseData()
    {

        // Проверка авторизации
        if (!$this->auth) {
            return view('components.error.default', ['error' => 'Ошибка доступа - Авторизуйтесь в системе.']);
        }

        // Даннные
        $inp['id'] = $this->request->input('id', null);

        // Xml для EnterpriseData
        $xml = (new ExportService())->financeOperationExportXmlForEnterpriceData($inp);

        header('Last-Modified: ' . gmdate('D,d M YH:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: text/plain; charset=utf-8');
        header('Content-Description: PHP Generated Data');

        echo $xml;

    }

    // Выгрузка на ftp для EnterpriseData
    public function xmlEnterpriseDataUnload()
    {

        // Проверка авторизации
        if (!$this->auth) {
            return view('components.error.default', ['error' => 'Ошибка доступа - Авторизуйтесь в системе.']);
        }

        // Даннные
        $inp['id'] = $this->request->input('id', null);

        // Xml для EnterpriseData
        $xml = (new ExportService())->financeOperationExportXmlForEnterpriceData($inp);

        // Фин. оперция
        $finance = (new Finance())->getOperation(['id' => $inp['id']]);

        // Имя файл
        $fileName = 'finance_' . $inp['id'] . '_v4.xml';

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

        } else {

            return 'Выгрузка не настроена по ИП';

        }

        return $data;

    }
}
