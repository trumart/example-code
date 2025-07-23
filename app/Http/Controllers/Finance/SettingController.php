<?php



namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SettingRequest;
use App\Http\Resources\Finance\SettingResource;
use App\Models\Finance\Setting;
use App\Models\User;
use App\Services\Finance\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $section = 'finance_setting';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    // Список настроек
    public function settings(SettingRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validated();

        // Финансовые операции
        $data = (new SettingService())->getSettings($inp);

        return SettingResource::collection($data);

    }

    // Добавляем операцию
    public function insert(SettingRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Онлайн
        (new User())->online($this->auth);

        // Данные
        $inp = $request->validate([
            'cat_id'    => ['required', 'numeric'],
            'inn'       => ['required', 'numeric'],
            'unloading' => ['nullable', 'boolean'],
            'nds'       => ['nullable', 'boolean'],
            'nds_val'   => ['nullable','numeric'],
        ]);

        $data = (new Setting())->insert($inp);

        return $data;

    }

    // Удаление
    public function remove(int $settingId)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        // Онлайн
        (new User())->online($this->auth);

        $data = (new Setting())->remove(['id' => $settingId]);

        return $data;

    }
}
