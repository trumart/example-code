<?php

namespace Finance;

use App\Models\Finance\Cat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {

        parent::setUp();

    }

    public function it_can_get_single_cat_by_filter(): void
    {
        $parent = Cat::factory()->create(['title' => 'Доходы']);
        $child  = Cat::factory()->create(['title' => 'Зарплата', 'parent_id' => $parent->id]);

        $result = (new Cat())->getCat(['id' => $parent->id]);

        $this->assertNotNull($result);
        $this->assertEquals('Доходы', $result->title);
        $this->assertTrue($result->children->contains($child));
    }

    public function it_can_get_multiple_cats_by_filter(): void
    {
        $parent = Cat::factory()->create(['title' => 'Расходы']);
        $child1 = Cat::factory()->create(['title' => 'Еда', 'parent_id' => $parent->id]);
        $child2 = Cat::factory()->create(['title' => 'Транспорт', 'parent_id' => $parent->id]);

        $results = (new Cat())->getCats(['parent_id' => $parent->id]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($child1));
        $this->assertTrue($results->contains($child2));
    }

    public function it_can_access_children_relation(): void
    {
        $parent = Cat::factory()->create(['title' => 'Инвестиции']);
        $child  = Cat::factory()->create(['title' => 'Акции', 'parent_id' => $parent->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertEquals($parent->id, $child->parent_id);
    }

    /** @test */
    public function it_can_insert_cat(): void
    {
        $inp = [
            'title' => 'Тестовая категория',
        ];

        $cat = (new Cat())->insert($inp);

        $this->assertNotFalse($cat, 'Insert вернул false, ожидался Cat');
        $this->assertInstanceOf(Cat::class, $cat);
        $this->assertDatabaseHas('finance_cat', [
            'id'    => $cat->id,
            'title' => 'Тестовая категория',
        ]);
    }

    /** @test */
    public function it_can_edit_cat(): void
    {
        $cat = Cat::factory()->create([
            'title' => 'Старая категория',
        ]);

        $inp = [
            'id'    => $cat->id,
            'title' => 'Новая категория',
        ];

        $updated = (new Cat())->edit($inp);

        $this->assertInstanceOf(Cat::class, $updated);
        $this->assertEquals('Новая категория', $updated->title);
        $this->assertDatabaseHas('finance_cat', [
            'id'    => $cat->id,
            'title' => 'Новая категория',
        ]);
    }

    /** @test */
    public function it_can_remove_cat(): void
    {
        $cat = Cat::factory()->create();

        $result = (new Cat())->remove($cat->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('finance_cat', [
            'id' => $cat->id,
        ]);
    }
}
