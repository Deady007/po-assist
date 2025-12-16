<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('module_templates')) {
            Schema::create('module_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->unsignedInteger('order_no')->default(1);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('project_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('project_modules', 'name')) {
                $table->string('name')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('project_modules', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('project_modules', 'order_no')) {
                $table->unsignedInteger('order_no')->default(1)->after('name');
            }
            if (Schema::hasColumn('project_modules', 'status')) {
                $table->string('status')->default('NOT_STARTED')->change();
            } else {
                $table->string('status')->default('NOT_STARTED');
            }
            if (!Schema::hasColumn('project_modules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('blocker_reason');
            }
            $foreignName = 'project_modules_template_id_foreign';
            if (!$this->constraintExists('project_modules', $foreignName)) {
                $table->foreign('template_id', $foreignName)->references('id')->on('module_templates')->nullOnDelete();
            }
            $uniqueName = 'project_modules_project_id_order_no_unique';
            if (!$this->indexExists('project_modules', $uniqueName)) {
                $table->unique(['project_id', 'order_no'], $uniqueName);
            }
            $statusIndex = 'project_modules_project_id_status_index';
            if (!$this->indexExists('project_modules', $statusIndex)) {
                $table->index(['project_id', 'status'], $statusIndex);
            }
        });

        // Backfill module name if missing.
        if (Schema::hasColumn('project_modules', 'module_name') && Schema::hasColumn('project_modules', 'name')) {
            DB::table('project_modules')->whereNull('name')->update(['name' => DB::raw('module_name')]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('project_module_id');
            }
            if (Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->default('TODO')->change();
            } else {
                $table->string('status')->default('TODO');
            }
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }
            if (!Schema::hasColumn('tasks', 'blocker_reason')) {
                $table->text('blocker_reason')->nullable()->after('due_date');
            }

            $foreignName = 'tasks_project_id_foreign';
            if (!$this->constraintExists('tasks', $foreignName)) {
                $table->foreign('project_id', $foreignName)->references('id')->on('projects')->cascadeOnDelete();
            }
            $assigneeIndex = 'tasks_assignee_user_id_status_index';
            if (!$this->indexExists('tasks', $assigneeIndex)) {
                $table->index(['assignee_user_id', 'status'], $assigneeIndex);
            }
            $statusIndex = 'tasks_project_id_status_index';
            if (!$this->indexExists('tasks', $statusIndex)) {
                $table->index(['project_id', 'status'], $statusIndex);
            }
        });

        // Backfill project_id on tasks based on module relationship.
        if (Schema::hasColumn('tasks', 'project_id') && Schema::hasColumn('tasks', 'project_module_id')) {
            DB::statement('UPDATE tasks t JOIN project_modules pm ON pm.id = t.project_module_id SET t.project_id = pm.project_id WHERE t.project_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['assignee_user_id', 'status']);
                $table->dropIndex(['project_id', 'status']);
                $table->dropColumn('project_id');
            }
            if (Schema::hasColumn('tasks', 'priority')) {
                $table->dropColumn('priority');
            }
        });

        Schema::table('project_modules', function (Blueprint $table) {
            if (Schema::hasColumn('project_modules', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }
            if (Schema::hasColumn('project_modules', 'order_no')) {
                $table->dropUnique(['project_id', 'order_no']);
                $table->dropColumn('order_no');
            }
            if (Schema::hasColumn('project_modules', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('project_modules', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::dropIfExists('module_templates');
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        $database = DB::getDatabaseName();

        $constraint = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$database, $table, $constraintName]
        );

        return $constraint !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $index = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $index !== null;
    }
};
