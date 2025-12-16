<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Services\ImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver missing');
        }

        parent::setUp();
    }

    public function test_import_records_row_errors(): void
    {
        Role::create(['name' => 'Admin']);

        $csv = "name,email,role,password\nBad User,bad-email,Admin,weakpass";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $service = app(ImportExportService::class);
        $job = $service->import('users', $file, null);

        $this->assertEquals('completed_with_errors', $job->status);
        $this->assertEquals(1, $job->error_count);
        $this->assertDatabaseHas('import_job_row_errors', [
            'import_job_id' => $job->id,
            'row_number' => 2,
        ]);
        $this->assertDatabaseCount('users', 0);
    }
}
