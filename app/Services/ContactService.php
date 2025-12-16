<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data): Contact
    {
        return DB::transaction(function () use ($data) {
            $contact = Contact::create($data);

            $this->syncPrimary($contact, $data['is_primary'] ?? false);
            $this->audit->logModel($contact, AuditLogger::ACTION_CREATE);

            return $contact->fresh('customer');
        });
    }

    public function update(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            $contact->fill($data);
            $contact->save();

            if (array_key_exists('is_primary', $data)) {
                $this->syncPrimary($contact, (bool) $data['is_primary']);
            }

            $this->audit->logModel($contact, AuditLogger::ACTION_UPDATE);

            return $contact->fresh('customer');
        });
    }

    public function delete(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            $contactId = $contact->id;
            $contact->delete();
            $this->audit->log('Contact', (string) $contactId, AuditLogger::ACTION_DELETE);
        });
    }

    private function syncPrimary(Contact $contact, bool $isPrimary): void
    {
        if ($isPrimary) {
            Contact::where('customer_id', $contact->customer_id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);

            $contact->is_primary = true;
            $contact->save();
        }
    }
}
