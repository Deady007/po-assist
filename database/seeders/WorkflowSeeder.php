<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\TokenWallet;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project = Project::first() ?? Project::create([
            'name' => 'Sample Project',
            'client_name' => 'Sample Client',
        ]);

        $phases = [
            1 => 'REQUIREMENTS',
            2 => 'DATA_COLLECTION',
            3 => 'MASTER_DATA_SETUP',
            4 => 'DEVELOPMENT',
            5 => 'TESTING',
            6 => 'DELIVERY',
        ];

        foreach ($phases as $sequence => $phaseKey) {
            ProjectPhase::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'phase_key' => $phaseKey,
                ],
                [
                    'sequence_no' => $sequence,
                    'status' => 'NOT_STARTED',
                ]
            );
        }

        TokenWallet::firstOrCreate(
            ['project_id' => $project->id],
            ['total_tokens' => 0, 'used_tokens' => 0]
        );
    }
}
