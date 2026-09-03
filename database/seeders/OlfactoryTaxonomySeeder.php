<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OlfactoryTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/taxonomia-sensorial-semilla.csv');
        $handle = fopen($path, 'r');
        fgetcsv($handle); // EDB 09/03/26: reads the first line, and skips it.

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) { // EDB 09/03/26: reads one line at the time.
            $rows[] = [
                'id' => $data[0],
                'ulid' => (string) Str::ulid(),
                'parent_id' => $data[1] ?: null,
                'level' => $data[2],
                'name_en' => $data[3],
                'name_es' => $data[4],
                'description_en' => $data[6] ?: null,
                'description_es' => $data[5] ?: null,
                'color_base' => $data[7] ?: null,
                'color' => $data[8] ?: null,
                'categories' => $data[9] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        DB::table('olfactory_taxonomies')->insert($rows);
    }
}
