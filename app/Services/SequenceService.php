<?php

namespace App\Services;

use App\Models\SequenceConfig;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SequenceService
{
    public function next(string $modelName): string
    {
        return DB::transaction(function () use ($modelName) {
            /** @var SequenceConfig|null $config */
            $config = SequenceConfig::where('model_name', $modelName)->lockForUpdate()->first();
            if (!$config) {
                throw new RuntimeException("Sequence configuration missing for {$modelName}");
            }

            $now = now();
            $shouldReset = false;
            if ($config->reset_policy === 'yearly' && $config->last_reset_at?->format('Y') !== $now->format('Y')) {
                $shouldReset = true;
            } elseif ($config->reset_policy === 'monthly' && $config->last_reset_at?->format('Ym') !== $now->format('Ym')) {
                $shouldReset = true;
            }

            if ($shouldReset) {
                $current = (int) $config->start_from;
                $config->last_reset_at = $now;
            } else {
                $base = (int) ($config->current_value ?? 0);
                $current = $base < $config->start_from ? (int) $config->start_from : $base + 1;
                $config->last_reset_at = $config->last_reset_at ?: $now;
            }

            $config->current_value = $current;
            $config->save();

            return $this->format($config, $current, $now);
        });
    }

    private function format(SequenceConfig $config, int $current, \DateTimeInterface $now): string
    {
        $seq = str_pad((string) $current, (int) ($config->padding ?? 4), '0', STR_PAD_LEFT);

        if ($config->format_template) {
            $replacements = [
                '{prefix}' => $config->prefix ?? '',
                '{seq}' => $seq,
                '{year}' => $now->format('Y'),
                '{month}' => $now->format('m'),
            ];

            return strtr($config->format_template, $replacements);
        }

        return ($config->prefix ?? '') . $seq;
    }
}
