<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Casa',
            'Departamento',
            'Local',
            'Townhouse',
            'Oficina',
            'Terreno',
        ];

        foreach ($types as $type) {
            PropertyType::updateOrCreate(
                ['slug' => Str::slug($type)],
                ['name' => $type, 'is_active' => true]
            );
        }
    }
}
