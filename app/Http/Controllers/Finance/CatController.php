<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CatInsertRequest;
use App\Http\Requests\Finance\CatRequest;
use App\Http\Resources\Finance\CatResource;
use App\Models\Finance\Cat;
use App\Services\Finance\CatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatController extends Controller
{

    public function __construct(Request $request)
    {

        parent::__construct($request);

    }

    /**
     * Фин категории
     *
     * @param CatRequest $request
     * @param Cat $catModel
     * @return AnonymousResourceCollection
     */
    public function cats(CatRequest $request, Cat $catModel): AnonymousResourceCollection
    {

        $inp              = $request->validated();
        $inp['parent_id'] = 0;

        return CatResource::collection($catModel->getCats($inp));

    }

    /**
     * Отчет по финансовой категориям
     *
     * @param CatRequest $request
     * @param CatService $catService
     * @return JsonResponse
     */
    public function report(CatRequest $request, CatService $catService): JsonResponse
    {

        $inp = $request->validate([
            'search.store'               => ['nullable', 'numeric', 'exists:stores,id'],
            'search.date_type'           => ['required', 'string'],
            'search.date_start'          => ['required', 'date'],
            'search.date_finish'         => ['required', 'date'],
            'search.date_start_compare'  => ['nullable', 'date'],
            'search.date_finish_compare' => ['nullable', 'date'],
        ]);

        $report = $catService->reportGroupCats($inp['search']);

        return response()->apiSuccess($report);

    }

    /**
     * Добавление
     *
     * @param CatInsertRequest $request
     * @return JsonResponse|CatResource
     */
    public function insert(CatInsertRequest $request): JsonResponse|CatResource
    {

        $data = (new Cat())->insert($request->validated());

        return $data
            ? new CatResource($data)
            : response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится — отправь скрин в чат');

    }

    /**
     * Удаление
     *
     * @param int $catId
     * @param CatService $catService
     * @return JsonResponse
     */
    public function remove(int $catId, CatService $catService): JsonResponse
    {

        return $catService->remove($catId);

    }

    /**
     * Редактирование
     *
     * @param CatRequest $request
     * @param CatService $catService
     * @return JsonResponse
     */
    public function edit(CatRequest $request, CatService $catService): JsonResponse
    {

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

        return $catService->edit($inp);

    }

    /**
     * Вверх
     *
     * @param CatRequest $request
     * @param CatService $catService
     * @return CatResource
     */
    public function up(CatRequest $request, CatService $catService): CatResource
    {

        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = $catService->updateNum($inp['id'], 'up');

        return new CatResource($data);

    }

    /**
     * Вниз
     *
     * @param CatRequest $request
     * @param CatService $catService
     * @return CatResource
     */
    public function down(CatRequest $request, CatService $catService): CatResource
    {

        $inp = $request->validate([
            'id' => ['required', 'numeric'],
        ]);

        $data = $catService->updateNum($inp['id'], 'down');

        return new CatResource($data);

    }
}
