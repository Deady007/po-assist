<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiClient;

class GeminiTest extends Command
{
    protected $signature = 'gemini:test {--json : Expect JSON output}';
    protected $description = 'Test Gemini API connectivity';

    public function handle(GeminiClient $gemini): int
    {
        $prompt = $this->option('json')
            ? 'Return STRICT JSON: {"ok": true, "message": "Gemini works"}'
            : 'Write one short sentence confirming the system is working.';

        try {
            if ($this->option('json')) {
                $out = $gemini->generateJson($prompt);
                $this->info(json_encode($out, JSON_PRETTY_PRINT));
            } else {
                $out = $gemini->generateText($prompt);
                $this->info($out);
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
