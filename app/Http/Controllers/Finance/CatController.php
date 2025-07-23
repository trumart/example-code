<?php



namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CatFilterRequest;
use App\Http\Requests\Finance\CatRequest;
use App\Http\Resources\Finance\CatResource;
use App\Models\Finance\Cat;
use App\Models\User;
use App\Services\Finance\CatService;
use Illuminate\Http\Request;

class CatController extends Controller
{
    protected $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    // Отчет по финансовым категориям
    public function cats(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp              = $request->validated();
        $inp['parent_id'] = 0;

        $data = (new Cat())->getCats($inp);

        return CatResource::collection($data);

    }

    //    // Полный отчет по магазину в рамках месяца
    //    public function reportStoreFinance(CatFilterRequest $request)
    //    {
    //
    //        // Проверка доступа
    //        (new User())->checkAccess('json', $this->auth, $this->section, 'view');
    //
    //        $inp = $request->validated();
    //
    //        $report = (new CatService())->reportGroupCats($inp);
    //
    //        return CatResource::collection($report);
    //
    //    }

    // Отчет по категорям
    public function report(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'search.store'               => ['nullable', 'numeric', 'exists:stores,id'],
            'search.date_type'           => ['required', 'string'],
            'search.date_start'          => ['required', 'date'],
            'search.date_finish'         => ['required', 'date'],
            'search.date_start_compare'  => ['nullable', 'date'],
            'search.date_finish_compare' => ['nullable', 'date'],
        ]);

        $report = (new CatService())->reportGroupCats($inp['search']);

        return json_encode(['data' => $report]);

    }

    // Сохранение
    public function insert(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'addition');

        // Данные
        $inp = $request->validate([
            'parent_id'  => ['required', 'numeric'],
            'title'      => ['required', 'string', 'unique:finance_cat,title', 'min:2', 'max:50'],
            'num'        => ['nullable'],
            'operating'  => ['nullable'],
            'nochange'   => ['nullable'],
            'noconsider' => ['nullable'],
            'document'   => ['nullable', 'boolean'],
        ]);

        $data = (new Cat())->insert($inp);

        return new CatResource($data);

    }

    // Удаление
    public function remove(int $catId)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        $data = (new Cat())->remove(['id' => $catId]);

        return new CatResource($data);

    }

    // Редактирование
    public function edit(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'id'         => ['required', 'numeric'],
            'parent_id'  => ['nullable', 'numeric'],
            'title'      => ['required', 'string', 'min:2', 'max:50'],
            'num'        => ['nullable', 'numeric'],
            'operating'  => ['nullable', 'boolean'],
            'nochange'   => ['nullable', 'boolean'],
            'noconsider' => ['nullable', 'boolean'],
            'document'   => ['nullable', 'boolean'],
        ]);

        $data = (new Cat())->edit($inp);

        return new CatResource($data);

    }

    // Вверх
    public function up(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = (new CatService())->updateNum($inp['id'], 'up');

        return new CatResource($data);

    }

    // Вниз
    public function down(CatRequest $request)
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'edit');

        // Данные
        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = (new CatService())->updateNum($inp['id'], 'down');

        return new CatResource($data);

    }
}
