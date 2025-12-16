<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\ProjectStatus;
use App\Models\Role;
use App\Models\SequenceConfig;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Admin', 'PM', 'Developer', 'Viewer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        SequenceConfig::firstOrCreate(
            ['model_name' => 'client'],
            [
                'prefix' => 'CL-',
                'padding' => 5,
                'start_from' => 1,
                'reset_policy' => 'none',
                'format_template' => '{prefix}{seq}',
            ]
        );

        SequenceConfig::firstOrCreate(
            ['model_name' => 'customer'],
            [
                'prefix' => 'CL-',
                'padding' => 5,
                'start_from' => 1,
                'reset_policy' => 'none',
                'format_template' => '{prefix}{seq}',
            ]
        );

        SequenceConfig::firstOrCreate(
            ['model_name' => 'project'],
            [
                'prefix' => 'PRJ-',
                'padding' => 5,
                'start_from' => 1,
                'reset_policy' => 'none',
                'format_template' => '{prefix}{seq}',
            ]
        );

        $statuses = [
            ['name' => 'Requirement Gathering', 'order_no' => 1, 'is_default' => true],
            ['name' => 'Data Collection', 'order_no' => 2],
            ['name' => 'DB Design', 'order_no' => 3],
            ['name' => 'Development', 'order_no' => 4],
            ['name' => 'Testing', 'order_no' => 5],
            ['name' => 'Delivered', 'order_no' => 6],
        ];

        foreach ($statuses as $status) {
            $created = ProjectStatus::firstOrCreate(
                ['name' => $status['name']],
                [
                    'order_no' => $status['order_no'],
                    'is_default' => $status['is_default'] ?? false,
                    'is_active' => true,
                ]
            );

            if (($status['is_default'] ?? false) && $created->is_default === false) {
                $created->update(['is_default' => true]);
            }

            if ($created->is_default) {
                ProjectStatus::where('id', '!=', $created->id)->update(['is_default' => false]);
            }
        }

        $templates = [
            ['code' => 'PRODUCT_UPDATE', 'name' => 'Product Update Email'],
            ['code' => 'MEETING_SCHEDULE', 'name' => 'Meeting Schedule Email'],
            ['code' => 'MOM_DRAFT', 'name' => 'MoM Draft'],
            ['code' => 'MOM_REFINED', 'name' => 'MoM Refined'],
            ['code' => 'MOM_FINAL', 'name' => 'MoM Final Email'],
            ['code' => 'HR_UPDATE', 'name' => 'HR End-of-Day'],
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(
                [
                    'code' => $template['code'],
                    'scope_type' => 'global',
                    'scope_id' => null,
                ],
                [
                    'name' => $template['name'],
                    'description' => null,
                ]
            );
        }

        $moduleTemplates = [
            ['key' => 'quotation', 'name' => 'Quotation', 'order_no' => 1],
            ['key' => 'requirements', 'name' => 'Requirements Management', 'order_no' => 2],
            ['key' => 'kickoff', 'name' => 'Kick-off Call', 'order_no' => 3],
            ['key' => 'data_collection', 'name' => 'Data Collection/Management', 'order_no' => 4],
            ['key' => 'development', 'name' => 'Development Assign', 'order_no' => 5],
            ['key' => 'testing', 'name' => 'Testing Assign', 'order_no' => 6],
            ['key' => 'review', 'name' => 'Review', 'order_no' => 7],
        ];

        foreach ($moduleTemplates as $tmpl) {
            \App\Models\ModuleTemplate::firstOrCreate(
                ['key' => $tmpl['key']],
                [
                    'name' => $tmpl['name'],
                    'order_no' => $tmpl['order_no'],
                    'is_active' => true,
                ]
            );
        }
    }
}
