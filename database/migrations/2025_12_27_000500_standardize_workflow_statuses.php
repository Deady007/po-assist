<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_modules')) {
            $moduleMap = [
                'Not Started' => 'NOT_STARTED',
                'NOT_STARTED' => 'NOT_STARTED',
                'In Progress' => 'IN_PROGRESS',
                'IN_PROGRESS' => 'IN_PROGRESS',
                'Blocked' => 'BLOCKED',
                'Done' => 'DONE',
            ];

            foreach ($moduleMap as $from => $to) {
                DB::table('project_modules')->where('status', $from)->update(['status' => $to]);
            }

            DB::table('project_modules')->whereNull('status')->update(['status' => 'NOT_STARTED']);
        }

        if (Schema::hasTable('tasks')) {
            $taskMap = [
                'Not Started' => 'TODO',
                'NOT_STARTED' => 'TODO',
                'In Progress' => 'IN_PROGRESS',
                'IN_PROGRESS' => 'IN_PROGRESS',
                'Blocked' => 'BLOCKED',
                'Done' => 'DONE',
            ];

            foreach ($taskMap as $from => $to) {
                DB::table('tasks')->where('status', $from)->update(['status' => $to]);
            }

            DB::table('tasks')->whereNull('status')->update(['status' => 'TODO']);

            $priorityMap = [
                'low' => 'LOW',
                'LOW' => 'LOW',
                'medium' => 'MEDIUM',
                'MEDIUM' => 'MEDIUM',
                'high' => 'HIGH',
                'HIGH' => 'HIGH',
            ];

            foreach ($priorityMap as $from => $to) {
                DB::table('tasks')->where('priority', $from)->update(['priority' => $to]);
            }
        }
    }

    public function down(): void
    {
        // No-op: keeping normalized workflow values.
    }
};
