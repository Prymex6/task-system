<?php

namespace App\Services;

use App\Models\Tenant\Automation;
use App\Models\Tenant\AutomationLog;
use App\Models\Tenant\Task;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public static function trigger(string $event, array $context = []): void
    {
        Automation::where('is_active', true)
            ->where('trigger_event', $event)
            ->get()
            ->each(fn ($automation) => static::run($automation, $context));
    }

    public static function run(Automation $automation, array $context = []): void
    {
        $start = microtime(true);

        try {
            if (!static::conditionsMet($automation, $context)) {
                return;
            }

            static::executeAction($automation, $context);

            AutomationLog::create([
                'automation_id' => $automation->id,
                'status' => 'success',
                'context' => $context,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            Log::error("AutomationService: failed [{$automation->id}]", ['error' => $e->getMessage()]);

            AutomationLog::create([
                'automation_id' => $automation->id,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'context' => $context,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        }
    }

    private static function conditionsMet(Automation $automation, array $context): bool
    {
        $conditions = $automation->conditions ?? [];
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            $actual = $context[$field] ?? null;

            $match = match ($operator) {
                'equals' => $actual == $value,
                'not_equals' => $actual != $value,
                'contains' => str_contains((string) $actual, (string) $value),
                'gt' => $actual > $value,
                'lt' => $actual < $value,
                default => true,
            };

            if (!$match) {
                return false;
            }
        }

        return true;
    }

    private static function executeAction(Automation $automation, array $context): void
    {
        match ($automation->action) {
            'send_email' => static::actionSendEmail($automation->action_config ?? [], $context),
            'assign_task' => static::actionAssignTask($automation->action_config ?? [], $context),
            'change_status' => static::actionChangeStatus($automation->action_config ?? [], $context),
            'send_notification' => static::actionSendNotification($automation->action_config ?? [], $context),
            'call_webhook' => app(WebhookDispatcherService::class)->dispatch(
                $automation->action_config['event'] ?? 'automation.triggered',
                $context
            ),
            default => null,
        };
    }

    private static function actionSendEmail(array $config, array $context): void
    {
        Log::info('Automation: send_email', ['to' => $config['to'] ?? 'unknown', 'context' => array_keys($context)]);
    }

    private static function actionAssignTask(array $config, array $context): void
    {
        if (!empty($context['task_id']) && !empty($config['user_id'])) {
            Task::find($context['task_id'])?->assignees()->syncWithoutDetaching([$config['user_id']]);
        }
    }

    private static function actionChangeStatus(array $config, array $context): void
    {
        if (!empty($context['task_id']) && !empty($config['status_id'])) {
            TaskService::changeStatus(Task::find($context['task_id']), $config['status_id']);
        }
    }

    private static function actionSendNotification(array $config, array $context): void
    {
        if (!empty($config['user_id']) && !empty($config['message'])) {
            $user = User::find($config['user_id']);
            if ($user) {
                NotificationService::send($user, 'automation', 'Automatyzacja', $config['message']);
            }
        }
    }
}
