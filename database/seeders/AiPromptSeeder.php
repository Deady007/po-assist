<?php

namespace Database\Seeders;

use App\Models\AiPrompt;
use App\Services\AiPromptRepository;
use Illuminate\Database\Seeder;

class AiPromptSeeder extends Seeder
{
    public function run(): void
    {
        $repo = new AiPromptRepository();
        $prompts = $repo->getDefaultPrompts();

        foreach ($prompts as $prompt) {
            $taskKey = $prompt['task_key'];
            $version = $prompt['version'];

            $existing = AiPrompt::where('task_key', $taskKey)
                ->where('version', $version)
                ->first();

            $hasActive = AiPrompt::where('task_key', $taskKey)
                ->where('is_active', true)
                ->exists();

            if (!$existing) {
                AiPrompt::create([
                    'task_key' => $taskKey,
                    'version' => $version,
                    'system_instructions' => $prompt['system_instructions'],
                    'output_schema_json' => $prompt['output_schema_json'],
                    'few_shot_examples_json' => $prompt['few_shot_examples_json'],
                    'is_active' => !$hasActive,
                ]);
                continue;
            }

            // Don't overwrite tuned prompts. Only ensure there's at least one active prompt per task.
            if (!$hasActive && !$existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }
        }
    }
}
