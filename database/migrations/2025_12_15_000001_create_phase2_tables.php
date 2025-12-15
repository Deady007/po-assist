<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('phase_key');
            $table->tinyInteger('sequence_no');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('status')->default('NOT_STARTED');
            $table->timestamps();

            $table->unique(['project_id', 'phase_key']);
            $table->index(['project_id', 'sequence_no']);
            $table->index('status');
        });

        Schema::create('rfp_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('source_text')->nullable();
            $table->json('extracted_json')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->text('drive_web_view_link')->nullable();
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('req_code');
            $table->string('title');
            $table->longText('description');
            $table->string('source_type');
            $table->string('source_ref_type')->nullable();
            $table->unsignedBigInteger('source_ref_id')->nullable();
            $table->string('priority');
            $table->string('status');
            $table->boolean('is_change_request')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'req_code']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
        });

        Schema::create('requirement_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained('requirements')->cascadeOnDelete();
            $table->integer('version_no');
            $table->json('payload_json');
            $table->timestamps();

            $table->unique(['requirement_id', 'version_no']);
            $table->index('requirement_id');
        });

        Schema::create('data_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('expected_format')->nullable();
            $table->string('owner');
            $table->date('due_date')->nullable();
            $table->string('status');
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'due_date']);
        });

        Schema::create('data_item_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_item_id')->constrained('data_items')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('drive_file_id')->nullable();
            $table->text('drive_web_view_link')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index('data_item_id');
        });

        Schema::create('master_data_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('requirements')->nullOnDelete();
            $table->string('object_name');
            $table->string('field_name');
            $table->string('change_type');
            $table->string('data_type')->nullable();
            $table->text('description')->nullable();
            $table->string('implemented_by')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->string('version_tag')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('requirement_id');
        });

        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamps();
        });

        Schema::create('requirement_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained('requirements')->cascadeOnDelete();
            $table->foreignId('developer_id')->constrained('developers')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->string('status');
            $table->date('eta_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('requirement_id');
            $table->index('developer_id');
            $table->index('status');
        });

        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('requirements')->nullOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('severity');
            $table->string('status');
            $table->string('reported_by')->nullable();
            $table->foreignId('assigned_to_developer_id')->nullable()->constrained('developers')->nullOnDelete();
            $table->foreignId('resolved_by_developer_id')->nullable()->constrained('developers')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('fixed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'severity']);
            $table->index('requirement_id');
        });

        Schema::create('testers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('requirements')->nullOnDelete();
            $table->string('title');
            $table->longText('steps');
            $table->longText('expected_result');
            $table->string('created_from');
            $table->timestamps();

            $table->index('project_id');
            $table->index('requirement_id');
        });

        Schema::create('test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tester_id')->constrained('testers')->cascadeOnDelete();
            $table->date('run_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'run_date']);
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_run_id')->constrained('test_runs')->cascadeOnDelete();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('defect_bug_id')->nullable()->constrained('bugs')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('test_run_id');
            $table->index('test_case_id');
            $table->index('status');
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('delivery_date');
            $table->json('delivered_requirements_json');
            $table->text('signoff_notes')->nullable();
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('token_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('total_tokens')->default(0);
            $table->integer('used_tokens')->default(0);
            $table->timestamps();

            $table->unique('project_id');
        });

        Schema::create('token_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->longText('description');
            $table->integer('tokens_estimated')->default(0);
            $table->string('status');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'type']);
        });

        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('phase_key');
            $table->string('drive_folder_id')->nullable();
            $table->text('drive_web_view_link')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'phase_key']);
        });

        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('phase_key');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('drive_folder_id')->nullable();
            $table->text('web_view_link')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'phase_key']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('validation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamp('generated_at');
            $table->json('report_json');
            $table->longText('report_html')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_reports');
        Schema::dropIfExists('drive_files');
        Schema::dropIfExists('drive_folders');
        Schema::dropIfExists('token_requests');
        Schema::dropIfExists('token_wallets');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('test_runs');
        Schema::dropIfExists('test_cases');
        Schema::dropIfExists('testers');
        Schema::dropIfExists('bugs');
        Schema::dropIfExists('requirement_assignments');
        Schema::dropIfExists('developers');
        Schema::dropIfExists('master_data_changes');
        Schema::dropIfExists('data_item_files');
        Schema::dropIfExists('data_items');
        Schema::dropIfExists('requirement_versions');
        Schema::dropIfExists('requirements');
        Schema::dropIfExists('rfp_documents');
        Schema::dropIfExists('project_phases');
    }
};
