<?php

namespace Database\Seeders\AppBase;

use App\Domain\AppBase\Models\Setting\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::updateOrCreate(['code' => 'ar'], [
            'title' =>  [
                'ar'    =>  'العربية',
                'en'    =>  'Arabic',
            ],
            'status' => 'active',
        ]);

        Language::updateOrCreate(['code' => 'en'], [
            'title' =>  [
                'ar'    =>  'الانجليزية',
                'en'    =>  'English',
            ],
            'status' => 'active',
        ]);
    }
}
