<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BankImport implements ToCollection
{
    /**
     * @param Collection $collection
    */
    public function collection(Collection $collection)
    {

        return $collection;

    }

    public function batchSize(): int
    {
        return 1000;
    }
}
