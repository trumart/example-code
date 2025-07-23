<?php



namespace App\Http\Controllers\Route;

use App\Http\Controllers\Controller;
use App\Http\Requests\Route\LoaderRequest;
use App\Models\User;
use App\Services\Route\LoaderService;
use Illuminate\Http\Request;

class LoaderController extends Controller
{
    protected $section = 'routes';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    public function routes()
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        $data = (new LoaderService())->getRoutesParam();

        return $data;

    }

    // Сохранение
    public function insert(LoaderRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp = $request->validate([
            'route_param_id' => ['required', 'numeric'],
            'price'          => ['required', 'numeric'],
            'worker'         => ['required', 'numeric'],
            'hour'           => ['required', 'numeric'],
            'check_indrive'  => ['nullable', 'boolean'],
        ]);

        $data = (new LoaderService())->insert($inp);

        return $data;

    }

    // Удаление
    public function remove(int $catId)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        $data = (new LoaderService())->remove(['id' => $catId]);

        return $data;

    }
}
