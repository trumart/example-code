<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinanceExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Организация.Код',
            'Подразделение.Код',
            'Касса.Подразделение.Код',
            'Тип операции',
            'Дата',
            'Название',
            'Описание',
            'Сумма',
            'Источник',
            'КатегорияРодитель.Код',
            'КатегорияРодитель.Название',
            'Категория.Код',
            'Категория.Название',
            'ИНН',
            'Сотрудник.Автор',
            'Сотрудник.Выплата',
            'Сотрудник.Согласовал',
            'Дата.Добавлено',
        ];
    }
}
