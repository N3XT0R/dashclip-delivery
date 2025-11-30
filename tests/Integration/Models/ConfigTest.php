<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Config;
use App\Models\Config\Category;
use Tests\DatabaseTestCase;

final class ConfigTest extends DatabaseTestCase
{
    public function testBelongsToCategory(): void
    {
        $category = Category::query()->create([
            'slug' => 'general',
            'name' => 'General',
            'is_visible' => true,
        ]);

        $config = Config::query()->create([
            'config_category_id' => $category->getKey(),
            'key' => 'feature.flags',
            'cast_type' => 'array',
            'value' => json_encode(['enabled' => true]),
            'selectable' => [],
            'is_visible' => true,
        ]);

        $this->assertTrue($config->category->is($category));
        $this->assertTrue($category->configs->contains($config));
    }

    public function testValueCastingAndValidation(): void
    {
        $config = Config::query()->create([
            'key' => 'feature.flags',
            'cast_type' => 'array',
            'value' => json_encode(['enabled' => true]),
            'selectable' => [],
            'is_visible' => true,
        ]);

        $this->assertIsArray($config->value);
        $this->assertTrue($config->value['enabled']);

        $config->value = 'hello world';
        $config->cast_type = 'string';
        $config->save();

        $this->assertSame('hello world', $config->fresh()->value);
    }
}
