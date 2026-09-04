<?php

namespace Database\Seeders;

use App\Models\CertificationType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CertificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/certification-types-seed.csv');
        $handle = fopen($path, 'r');

        fgetcsv($handle); // salta la cabecera (code,description)

        while (($row = fgetcsv($handle)) !== false) {
            CertificationType::create([
                'code' => $row[0],
                'description' => $row[1],
            ]);
        }

        fclose($handle);
    }
}
