<?php

namespace App\Services\Rest;

use App\Models\Item;
use App\Models\ItemKit;
use App\Models\Rest;
use Intervention\Image\Collection;

class RestService
{
    /**
     * Выборочная инвентаризация
     *
     * @param array $inp
     * @return Collection Rests остатки
     */
    public function inventorySelective(array $inp): Collection
    {

        $arrRests = [];

        // Остатки
        foreach ($inp as $k => $val) {

            // Если заявка
            if (substr_count($k, 'rest_') == 0) {
                continue;
            }

            if ($val != 'on') {
                continue;
            }

            $arrRests[] = str_replace('rest_', '', $k);

        }

        $arrRests = array_values($arrRests);
        $arrRests = array_unique($arrRests);

        // Остатки
        $rests = (new Rest())->getRests([
            'id'     => $arrRests,
            'status' => ['в наличии', 'на проверке'],
        ]);

        foreach ($rests as $rest) {

            // Товар
            $item = (new Item())->getItem(['id' => $rest->item], true);

            // Комплект
            $rest->kit = (new ItemKit())->getItems(['item' => $item->id]);

        }

        return $rests;

    }
}
