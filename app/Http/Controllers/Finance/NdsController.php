<?php



namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Finance\NdsService;
use Illuminate\Http\Request;

class NdsController extends Controller
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
        (new User())->checkAccess('page', $this->auth, $this->section, 'remove');

        return view('page.finance_nds', [
            'auth' => $this->auth,
        ]);

    }

    // НДС
    public function nds()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        // Данные
        $inp['date_start']  = $this->request->input('date_start', date('Y-m-') . '01');
        $inp['date_finish'] = $this->request->input('date_finish', date('Y-m-d'));

        $inp['date_start']  = date('Y-m-d', strtotime($inp['date_start']));
        $inp['date_finish'] = date('Y-m-d', strtotime($inp['date_finish']));

        // Отчет по НДС
        $data = (new NdsService())->getReport($inp);

        return json_encode($data);

    }
}
