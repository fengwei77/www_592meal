<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 建立一個 Store 並使用其 ID 生成 schema_name
        $store = \App\Models\Store::factory()->create();

        return [
            'store_id' => $store->id,
            'schema_name' => "tenant_{$store->id}",
            'metadata' => [],
        ];
    }

    /**
     * 為指定的 Store 建立 Tenant
     *
     * @param \App\Models\Store $store
     * @return static
     */
    public function forStore(\App\Models\Store $store): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $store->id,
            'schema_name' => "tenant_{$store->id}",
        ]);
    }

    /**
     * 使用自訂 schema_name
     *
     * @param string $schemaName
     * @return static
     */
    public function withSchemaName(string $schemaName): static
    {
        return $this->state(fn (array $attributes) => [
            'schema_name' => $schemaName,
        ]);
    }
}
