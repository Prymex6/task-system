<?php

namespace App\Services;

use App\Models\Tenant\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    private static function token(): ?string
    {
        return IntegrationService::get('github')?->config['token'] ?? null;
    }

    public static function linkCommitToTask(string $commitMessage, string $sha, string $repoUrl): void
    {
        preg_match_all('/#(\d+)/', $commitMessage, $matches);
        foreach ($matches[1] as $taskId) {
            $task = Task::find((int) $taskId);
            if ($task) {
                $task->comments()->create([
                    'body' => "🔗 Commit powiązany: [{$sha}]({$repoUrl}/commit/{$sha})\n\n```\n{$commitMessage}\n```",
                    'user_id' => null,
                    'is_internal' => true,
                ]);
            }
        }
    }

    public static function getRepoIssues(string $owner, string $repo): array
    {
        $token = static::token();
        if (!$token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/issues");

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('GitHubService: failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
