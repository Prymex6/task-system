<?php

namespace App\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use App\Models\Tenant\Lead;
use App\Models\Tenant\User;

class LeadService
{
    public static function convertToClient(Lead $lead, User $by): Client
    {
        $client = Client::create([
            'name' => $lead->company_name ?? $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'is_active' => true,
            'created_by' => $by->id,
        ]);

        ClientContact::create([
            'client_id' => $client->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'is_primary' => true,
        ]);

        $lead->update([
            'status' => 'converted',
            'converted_client_id' => $client->id,
            'converted_at' => now(),
            'converted_by' => $by->id,
        ]);

        AuditService::log('lead.converted', $lead, [], ['client_id' => $client->id]);

        return $client;
    }

    public static function addActivity(Lead $lead, array $data, User $by): void
    {
        $lead->activities()->create([
            'type' => $data['type'],
            'description' => $data['description'],
            'date' => $data['date'] ?? today(),
            'user_id' => $by->id,
            'next_action' => $data['next_action'] ?? null,
        ]);
    }
}
