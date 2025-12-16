<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private SequenceService $sequences,
        private AuditLogger $audit
    ) {}

    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $code = $data['customer_code'] ?? $this->sequences->next('customer');

            $customer = Customer::create([
                'customer_code' => $code,
                'name' => $data['name'],
                'industry' => $data['industry'] ?? null,
                'website' => $data['website'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->logModel($customer, AuditLogger::ACTION_CREATE);

            return $customer;
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            if (empty($data['customer_code'])) {
                $data['customer_code'] = $customer->customer_code ?: $this->sequences->next('customer');
            }

            $customer->update($data);
            $this->audit->logModel($customer, AuditLogger::ACTION_UPDATE);

            return $customer;
        });
    }

    public function toggle(Customer $customer): Customer
    {
        $customer->is_active = !$customer->is_active;
        $customer->save();

        $action = $customer->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($customer, $action);

        return $customer;
    }
}
