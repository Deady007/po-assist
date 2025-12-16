<?php

namespace Tests\Feature;

use App\Models\ProjectStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStatusDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_status_stays_default(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.config.statuses.store'), [
            'name' => 'Draft',
            'order_no' => 1,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $this->post(route('admin.config.statuses.store'), [
            'name' => 'In Flight',
            'order_no' => 2,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $defaults = ProjectStatus::where('is_default', true)->pluck('name')->all();

        $this->assertCount(1, $defaults);
        $this->assertEquals(['In Flight'], $defaults);
    }
}
