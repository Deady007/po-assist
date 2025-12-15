<?php

namespace Database\Seeders;

use App\Models\Developer;
use App\Models\Tester;
use Illuminate\Database\Seeder;

class DirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $developers = [
            ['name' => 'Alice Dev', 'email' => 'alice.dev@example.com'],
            ['name' => 'Bob Builder', 'email' => 'bob.builder@example.com'],
            ['name' => 'Charlie Coder', 'email' => 'charlie.coder@example.com'],
        ];

        foreach ($developers as $dev) {
            Developer::firstOrCreate(['email' => $dev['email']], $dev);
        }

        $testers = [
            ['name' => 'Tina Tester', 'email' => 'tina.tester@example.com'],
            ['name' => 'Quinn QA', 'email' => 'quinn.qa@example.com'],
        ];

        foreach ($testers as $tester) {
            Tester::firstOrCreate(['email' => $tester['email']], $tester);
        }
    }
}
