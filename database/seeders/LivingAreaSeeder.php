<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LivingAreaSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        $rows = collect(config('thunderpoint.living_areas'))
            ->values()
            ->map(function (array $area, int $index) use ($timestamp): array {
                return [
                    'name' => $area['name'],
                    'slug' => $area['slug'],
                    'deep_color' => $area['deep_color'],
                    'soft_color' => $area['soft_color'],
                    'display_order' => $index + 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        DB::table('living_areas')->upsert(
            $rows,
            ['slug'],
            ['name', 'deep_color', 'soft_color', 'display_order', 'updated_at']
        );
    }
}