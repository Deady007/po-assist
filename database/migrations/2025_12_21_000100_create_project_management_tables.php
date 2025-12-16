<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('industry');
            $table->index('is_active');
        });

        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('order_no')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('status_transition_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_status_id')->constrained('project_statuses')->cascadeOnDelete();
            $table->foreignId('to_status_id')->constrained('project_statuses')->cascadeOnDelete();
            $table->json('allowed_role_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['from_status_id', 'to_status_id']);
        });

        Schema::create('sequence_configs', function (Blueprint $table) {
            $table->id();
            $table->string('model_name')->unique();
            $table->string('prefix')->nullable();
            $table->unsignedInteger('padding')->default(4);
            $table->unsignedBigInteger('start_from')->default(1);
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('reset_policy')->default('none'); // none/yearly/monthly
            $table->string('format_template')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('file_name');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('status')->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('import_job_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained('import_jobs')->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('field_name')->nullable();
            $table->text('error_message');
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('scope_type')->default('global'); // global/client/project
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['code', 'scope_type', 'scope_id']);
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('project_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('module_name');
            $table->string('status')->default('Not Started');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('blocker_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_module_id')->constrained('project_modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Not Started');
            $table->date('due_date')->nullable();
            $table->text('blocker_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['project_module_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('project_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_project')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('password_hash')->nullable()->after('password');
            $table->foreignId('role_id')->nullable()->after('password_hash')->constrained('roles')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code')->nullable()->unique()->after('id');
            $table->foreignId('client_id')->nullable()->after('project_code')->constrained('clients')->nullOnDelete();
            $table->text('description')->nullable()->after('name');
            $table->foreignId('status_id')->nullable()->after('description')->constrained('project_statuses')->nullOnDelete();
            $table->date('start_date')->nullable()->after('status_id');
            $table->date('due_date')->nullable()->after('start_date');
            $table->string('priority')->default('medium')->after('due_date');
            $table->foreignId('owner_user_id')->nullable()->after('priority')->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('owner_user_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes();
        });

        Schema::table('email_artifacts', function (Blueprint $table) {
            $table->foreignId('email_template_id')->nullable()->after('type')->constrained('email_templates')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->after('project_id')->constrained('clients')->nullOnDelete();
            $table->string('scope_type')->default('project')->after('email_template_id');
            $table->unsignedBigInteger('generated_by')->nullable()->after('tone');
            $table->unsignedBigInteger('created_by')->nullable()->after('generated_by');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->json('variables_json')->nullable();
            $table->timestamp('generated_at');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'generated_at']);
            $table->index(['client_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::table('email_artifacts', function (Blueprint $table) {
            if (Schema::hasColumn('email_artifacts', 'email_template_id')) {
                $table->dropConstrainedForeignId('email_template_id');
            }
            if (Schema::hasColumn('email_artifacts', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
            if (Schema::hasColumn('email_artifacts', 'scope_type')) {
                $table->dropColumn('scope_type');
            }
            if (Schema::hasColumn('email_artifacts', 'generated_by')) {
                $table->dropColumn('generated_by');
            }
            if (Schema::hasColumn('email_artifacts', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('email_artifacts', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'project_code')) {
                $table->dropUnique('projects_project_code_unique');
                $table->dropColumn('project_code');
            }
            if (Schema::hasColumn('projects', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
            if (Schema::hasColumn('projects', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('projects', 'status_id')) {
                $table->dropConstrainedForeignId('status_id');
            }
            if (Schema::hasColumn('projects', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('projects', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('projects', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('projects', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }
            if (Schema::hasColumn('projects', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('projects', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('projects', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('projects', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'password_hash')) {
                $table->dropColumn('password_hash');
            }
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
        Schema::dropIfExists('project_teams');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_modules');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('import_job_row_errors');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('sequence_configs');
        Schema::dropIfExists('status_transition_rules');
        Schema::dropIfExists('project_statuses');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('roles');
    }
};
