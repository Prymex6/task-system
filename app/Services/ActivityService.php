<?php

namespace App\Services;

use App\Models\Tenant\Lead;
use App\Models\Tenant\LeadActivity;
use App\Models\Tenant\User;

class ActivityService
{
    public static function log(string $subjectType, int $subjectId, string $type, string $description, User $by, array $extra = []): void
    {
        LeadActivity::create(array_merge([
            'activityable_type' => $subjectType,
            'activityable_id' => $subjectId,
            'type' => $type,
            'description' => $description,
            'user_id' => $by->id,
            'date' => today(),
        ], $extra));
    }

    public static function forLead(Lead $lead): array
    {
        return $lead->activities()->with('user')->orderByDesc('date')->get()->toArray();
    }
}
