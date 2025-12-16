<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('due_date')->nullable(false)->default(now()->toDateString())->change();
            $table->index(['client_id', 'status_id']);
            $table->index('due_date');
        });

        Schema::table('project_teams', function (Blueprint $table) {
            $table->index(['project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('due_date')->nullable()->change();
            $table->dropIndex(['projects_client_id_status_id_index']);
            $table->dropIndex(['projects_due_date_index']);
        });

        Schema::table('project_teams', function (Blueprint $table) {
            $table->dropIndex(['project_teams_project_id_user_id_index']);
        });
    }
};
