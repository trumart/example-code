<?php

namespace App\Services\Finance;

use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CatService extends BaseService
{
    /**
     * Сводный отчет по фин категориям
     */
    public function reportGroupCats(array $inp): Collection
    {

        $financeModel    = new Finance();
        $financeCatModel = new Cat();

        $cats = $financeCatModel->getCats([
            'parent_id' => 0,
        ]);

        foreach ($cats as $cat) {

            $arrSubCatId = [];

            foreach ($cat->children as $subcat) {

                $arrSubCatId[] = $subcat->id;

                // Сумма операций
                $subcat->sum = $financeModel->sumOperations([
                    'store_id'    => $inp['store_id'] ?? null,
                    'cat_id'      => $subcat->id,
                    'type'        => 'расход',
                    'date_start'  => $inp['date_start'],
                    'date_finish' => $inp['date_finish'],
                    'date_type'   => $inp['date_type'],
                ]);

                $subcat->sum = round($subcat->sum, 2);

                if (!empty($inp['date_start_compare']) && !empty($inp['date_finish_compare'])) {

                    // Сумма операций
                    $subcat->sum_compare = $financeModel->sumOperations([
                        'store_id'    => $inp['store_id'] ?? null,
                        'cat_id'      => $subcat->id,
                        'type'        => 'расход',
                        'date_start'  => $inp['date_start_compare'],
                        'date_finish' => $inp['date_finish_compare'],
                        'date_type'   => $inp['date_type'],
                    ]);

                    $subcat->sum_compare = round($subcat->sum, 2);

                    $subcat->sum_diff = $subcat->sum - $subcat->sum_compare;

                }
            }

            $arrSubCatId[] = $cat->id;

            // Сумма операций по категории
            $cat->sum = $financeModel->sumOperations([
                'store_id'    => $inp['store_id'] ?? null,
                'cat_id'      => $arrSubCatId,
                'type'        => 'расход',
                'date_start'  => $inp['date_start'],
                'date_finish' => $inp['date_finish'],
                'date_type'   => $inp['date_type'],
            ]);

            $cat->sum = round($cat->sum, 2);

            if (!empty($inp['date_start_compare']) && !empty($inp['date_finish_compare'])) {

                // Сумма операций
                $cat->sum_compare = $financeModel->sumOperations([
                    'store_id'    => $inp['store_id'] ?? null,
                    'cat_id'      => $arrSubCatId,
                    'type'        => 'расход',
                    'date_start'  => $inp['date_start_compare'],
                    'date_finish' => $inp['date_finish_compare'],
                    'date_type'   => $inp['date_type'],
                ]);

                $cat->sum_compare = round($cat->sum, 2);

                $cat->sum_diff = $cat->sum - $cat->sum_compare;

            }
        }

        return $cats;

    }

    /**
     * Обновление сортировки
     */
    public function updateNum(int $cat_id, string $direction): Cat|JsonResponse
    {

        $financeCatModel = new Cat();

        // Категория
        $catA = $financeCatModel->getCat(['id' => $cat_id]);

        // Категория
        $catB = $financeCatModel->getCat([
            'parent_id' => $catA->parent_id,
            'num'       => $direction == 'up' ? $catA->num - 1 : $catA->num + 1,
        ]);

        if (empty($catB)) {

            $catA = $financeCatModel->updateNum([
                'id'  => $catA->id,
                'num' => $direction == 'up' ? $catA->num - 1 : $catA->num + 1,
            ]);

            if (!$catA) {
                return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
            }

            return $catA;

        }

        $cat = $financeCatModel->updateNum([
            'id'  => $catA->id,
            'num' => $catB->num
        ]);

        if (!$cat) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        $catB = $financeCatModel->updateNum([
            'id'  => $catB->id,
            'num' => $catA->num,
        ]);

        if (!$catB) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return $cat;

    }

    public function edit(array $inp): JsonResponse
    {

        $update = (new Cat())->edit($inp);

        if (!$update) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess($update);

    }

    public function remove(int $catId): JsonResponse
    {

        $delete = (new Cat())->remove($catId);

        if (!$delete) {
            return response()->apiError('Упс, что-то сломалось. Попробуй еще раз, а если не получится отправь скрин в чат');
        }

        return response()->apiSuccess();

    }
}
