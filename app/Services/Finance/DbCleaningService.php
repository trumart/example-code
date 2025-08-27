<?php

namespace App\Services\Finance;

use App\Models\Finance\Finance;
use Illuminate\Support\Facades\DB;

/**
 * Класс в котором используются методы для наведения порядка в базе данных в следствии каких то изменений
 */
class DbCleaningService extends BankService
{
    /**
     * Очистка дублирующих записей в таблице фин операций по банку
     * @return void
     */
    public function financesCleaningFromDouble(): void
    {

        $finances = Finance::select('code', 'cat_id', 'inn', DB::raw('COUNT(id) as count'))
            ->whereNotNull('code')
            ->groupBy('code', 'cat_id', 'inn')
            ->orderByDesc('count')
            ->get();

        foreach ($finances as $finance) {

            if ($finance->count < 2) {
                continue;
            }

            // Выбираем 1 строку
            $operation = (new Finance())->getOperation([
                'code'   => $finance->code,
                'cat_id' => $finance->cat_id,
                'inn'    => $finance->inn,
            ]);

            echo '- удалено ' . $operation->id . ' / ' . $finance->code . ' - ' . $finance->count . "\r\n";

            // Удаляем все остальные
            $delete = Finance::where('code', $finance->code)
                ->where('cat_id', $finance->cat_id)
                ->where('inn', $finance->inn)
                ->where('id', '!=', $operation->id)
                ->delete();

            if (!$delete) {
                echo '- не удалено ' . $operation->id . "\r\n";
            } else {
                echo '- удалено все кроме ' . $operation->id . "\r\n";
            }

        }

        echo 'continue';

    }
}
