<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task_key');
            $table->unsignedInteger('prompt_version')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('input_context_json')->nullable();
            $table->string('model_name')->nullable();
            $table->decimal('temperature', 4, 2)->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->longText('raw_output_text')->nullable();
            $table->json('parsed_output_json')->nullable();
            $table->timestamps();

            $table->index(['task_key', 'project_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
