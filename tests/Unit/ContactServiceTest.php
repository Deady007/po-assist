<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\SequenceConfig;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_mysql driver missing');
        }

        parent::setUp();

        SequenceConfig::create([
            'model_name' => 'customer',
            'prefix' => 'CL-',
            'padding' => 5,
            'start_from' => 1,
            'current_value' => 0,
            'reset_policy' => 'none',
        ]);
    }

    public function test_only_one_primary_contact_is_enforced(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CL-00001',
            'name' => 'Acme Inc',
        ]);

        $service = app(ContactService::class);

        $first = $service->create([
            'customer_id' => $customer->id,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'is_primary' => true,
        ]);

        $second = $service->create([
            'customer_id' => $customer->id,
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'is_primary' => true,
        ]);

        $this->assertTrue($second->is_primary);
        $this->assertFalse(Contact::find($first->id)->is_primary);
        $this->assertEquals(1, Contact::where('customer_id', $customer->id)->where('is_primary', true)->count());
    }
}
