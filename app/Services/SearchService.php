<?php

namespace App\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\KbArticle;
use App\Models\Tenant\Project;
use App\Models\Tenant\Task;
use App\Models\Tenant\Ticket;
use App\Models\Tenant\User;

class SearchService
{
    /**
     * @return list<array{type: string, id: int, title: string, subtitle: string, url: string}>
     */
    public static function search(string $query, User $user): array
    {
        $q = trim($query);

        if (strlen($q) < 2) {
            return [];
        }

        $like = "%{$q}%";
        $results = [];

        // Anyone below admin sees only what they are on, so the scoping is part
        // of the query rather than a filter over the results: a title alone
        // tells you a project exists.
        $projects = Project::where('name', 'like', $like)->limit(5);

        if (!$user->isAdmin()) {
            $projects->whereHas('members', fn ($m) => $m->where('user_id', $user->id));
        }

        foreach ($projects->get() as $project) {
            $results[] = [
                'type' => 'project',
                'id' => $project->id,
                'title' => $project->name,
                'subtitle' => __('messages.search_project'),
                'url' => route('tenant.manager.projects.show', $project),
            ];
        }

        $tasks = Task::where('title', 'like', $like)->limit(5);

        if (!$user->isAdmin()) {
            $tasks->whereHas('project.members', fn ($m) => $m->where('user_id', $user->id));
        }

        foreach ($tasks->get() as $task) {
            $results[] = [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->title,
                'subtitle' => $task->project?->name ?? __('messages.search_task'),
                'url' => route('tenant.manager.tasks.show', $task),
            ];
        }

        foreach (Client::where('name', 'like', $like)->limit(5)->get() as $client) {
            $results[] = [
                'type' => 'client',
                'id' => $client->id,
                'title' => $client->name,
                'subtitle' => __('messages.search_client'),
                'url' => route('tenant.manager.clients.show', $client),
            ];
        }

        foreach (Invoice::where('number', 'like', $like)->limit(3)->get() as $invoice) {
            $results[] = [
                'type' => 'invoice',
                'id' => $invoice->id,
                'title' => $invoice->number,
                'subtitle' => __('messages.search_invoice'),
                'url' => route('tenant.manager.invoices.show', $invoice),
            ];
        }

        // A ticket's heading is its subject. Searching 'title' here threw on
        // every query, so the whole screen was a 500 rather than a short
        // result list.
        foreach (Ticket::where('subject', 'like', $like)->limit(3)->get() as $ticket) {
            $results[] = [
                'type' => 'ticket',
                'id' => $ticket->id,
                'title' => $ticket->subject,
                'subtitle' => __('messages.search_ticket'),
                'url' => route('tenant.manager.tickets.show', $ticket),
            ];
        }

        foreach (KbArticle::where('title', 'like', $like)->where('is_published', true)->limit(3)->get() as $article) {
            $results[] = [
                'type' => 'kb',
                'id' => $article->id,
                'title' => $article->title,
                'subtitle' => __('messages.search_knowledge_base'),
                'url' => route('tenant.manager.kb.show', $article),
            ];
        }

        return $results;
    }
}
