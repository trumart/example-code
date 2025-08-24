<?php

namespace Finance;

use App\Models\Finance\Cat;
use App\Models\Finance\Finance;
use App\Models\Finance\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // Создадим связанные сущности
        $this->cat     = Cat::factory()->create();
        $this->company = Finance::factory()->create();
    }

    public function it_can_insert_setting(): void
    {
        $inp = [
            'inn'       => $this->company->inn,
            'cat_id'    => $this->cat->id,
            'text'      => 'Настройка тестовая',
            'unloading' => true,
            'doc_close' => false,
            'nds'       => true,
            'nds_val'   => 20,
        ];

        $setting = (new Setting())->insert($inp);

        $this->assertNotFalse($setting, 'Insert вернул false, ожидался Setting');
        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertDatabaseHas('finance_setting', [
            'id'  => $setting->id,
            'inn' => $this->company->inn,
        ]);

    }

    public function it_can_edit_setting(): void
    {
        $setting = Setting::factory()->create([
            'inn'    => $this->company->inn,
            'cat_id' => $this->cat->id,
            'text'   => 'Старый текст',
        ]);

        $inp = [
            'id'   => $setting->id,
            'text' => 'Новый текст',
        ];

        $updated = (new Setting())->edit($inp);

        $this->assertInstanceOf(Setting::class, $updated);
        $this->assertEquals('Новый текст', $updated->text);
        $this->assertDatabaseHas('finance_setting', [
            'id'   => $setting->id,
            'text' => 'Новый текст',
        ]);
    }

    public function it_can_remove_setting(): void
    {
        $setting = Setting::factory()->create();

        $result = (new Setting())->remove($setting->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('finance_setting', [
            'id' => $setting->id,
        ]);
    }

    public function it_can_get_single_setting_with_filter(): void
    {
        $setting = Setting::factory()->create([
            'inn'    => $this->company->inn,
            'cat_id' => $this->cat->id,
        ]);

        $found = (new Setting())->getSetting([
            'inn' => $this->company->inn,
        ]);

        $this->assertInstanceOf(Setting::class, $found);
        $this->assertEquals($setting->id, $found->id);
    }

    public function it_can_get_multiple_settings_with_filter(): void
    {
        Setting::factory()->count(3)->create([
            'inn'    => $this->company->inn,
            'cat_id' => $this->cat->id,
        ]);

        $items = (new Setting())->getSettings([
            'inn' => $this->company->inn,
        ]);

        $this->assertCount(3, $items);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $items);
    }
}
