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
        $defaults = (new \ReflectionClass($repo))->getProperty('defaults');
        $defaults->setAccessible(true);
        $prompts = $defaults->getValue($repo);

        foreach ($prompts as $prompt) {
            AiPrompt::updateOrCreate(
                ['task_key' => $prompt['task_key'], 'version' => $prompt['version']],
                [
                    'system_instructions' => $prompt['system_instructions'],
                    'output_schema_json' => $prompt['output_schema_json'],
                    'few_shot_examples_json' => $prompt['few_shot_examples_json'],
                    'is_active' => true,
                ]
            );
        }
    }
}
