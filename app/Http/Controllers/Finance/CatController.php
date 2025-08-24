<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CatRequest;
use App\Http\Resources\Finance\CatResource;
use App\Models\Finance\Cat;
use App\Models\User;
use App\Services\Finance\CatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatController extends Controller
{
    protected string $section = 'finance';

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Фин категории
     *
     * @param CatRequest $request
     * @return AnonymousResourceCollection
     */
    public function cats(CatRequest $request): AnonymousResourceCollection
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'view');

        // Данные
        $inp              = $request->validated();
        $inp['parent_id'] = 0;

        $data = (new Cat())->getCats($inp);

        return CatResource::collection($data);

    }

    /**
     * Отчет по финансовой категориям
     *
     * @param CatRequest $request
     * @return JsonResponse
     */
    public function report(CatRequest $request): JsonResponse
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

        return response()->apiSuccess($report);

    }

    /**
     * Добавление
     *
     * @param CatRequest $request
     * @return JsonResponse|CatResource
     */
    public function insert(CatRequest $request): JsonResponse|CatResource
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

        if (!$data) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return new CatResource($data);

    }

    /**
     * Удаление
     *
     * @param int $catId
     * @return JsonResponse
     */
    public function remove(int $catId): JsonResponse
    {

        // Проверка доступа
        (new User())->checkAccess('json', $this->auth, $this->section, 'remove');

        return (new CatService())->remove($catId);

    }

    /**
     * Редактирование
     *
     * @param CatRequest $request
     * @return JsonResponse
     */
    public function edit(CatRequest $request): JsonResponse
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

        return (new CatService())->edit($inp);

    }

    /**
     * Вверх
     *
     * @param CatRequest $request
     * @return CatResource
     */
    public function up(CatRequest $request): CatResource
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

    /**
     * Вниз
     *
     * @param CatRequest $request
     * @return CatResource
     */
    public function down(CatRequest $request): CatResource
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
