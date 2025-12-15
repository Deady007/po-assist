<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\AiPromptSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WorkflowSeeder::class,
            DirectorySeeder::class,
            SampleDataSeeder::class,
            AiPromptSeeder::class,
        ]);
    }
}
