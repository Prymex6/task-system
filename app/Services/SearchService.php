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
    public static function search(string $query, User $user): array
    {
        $q = trim($query);
        if (strlen($q) < 2) {
            return [];
        }

        $like = "%{$q}%";
        $results = [];

        // Projekty
        $projectsQuery = Project::where('name', 'like', $like)->limit(5);
        if (!$user->isAdmin()) {
            $projectsQuery->whereHas('members', fn ($m) => $m->where('user_id', $user->id));
        }
        foreach ($projectsQuery->get() as $p) {
            $results[] = ['type' => 'project', 'id' => $p->id, 'title' => $p->name, 'subtitle' => 'Projekt', 'url' => route('tenant.manager.projects.show', $p)];
        }

        // Zadania
        $tasksQuery = Task::where('title', 'like', $like)->limit(5);
        if (!$user->isAdmin()) {
            $tasksQuery->whereHas('project.members', fn ($m) => $m->where('user_id', $user->id));
        }
        foreach ($tasksQuery->get() as $t) {
            $results[] = ['type' => 'task', 'id' => $t->id, 'title' => $t->title, 'subtitle' => $t->project?->name ?? 'Zadanie', 'url' => route('tenant.manager.tasks.show', $t)];
        }

        // Clients
        foreach (Client::where('name', 'like', $like)->limit(5)->get() as $c) {
            $results[] = ['type' => 'client', 'id' => $c->id, 'title' => $c->name, 'subtitle' => 'Klient', 'url' => route('tenant.manager.clients.show', $c)];
        }

        // Invoices
        foreach (Invoice::where('number', 'like', $like)->limit(3)->get() as $i) {
            $results[] = ['type' => 'invoice', 'id' => $i->id, 'title' => $i->number, 'subtitle' => 'Faktura', 'url' => route('tenant.manager.invoices.show', $i)];
        }

        // Tickety
        foreach (Ticket::where('title', 'like', $like)->limit(3)->get() as $t) {
            $results[] = ['type' => 'ticket', 'id' => $t->id, 'title' => $t->title, 'subtitle' => 'Ticket', 'url' => route('tenant.manager.tickets.show', $t)];
        }

        // KB
        foreach (KbArticle::where('title', 'like', $like)->where('is_published', true)->limit(3)->get() as $a) {
            $results[] = ['type' => 'kb', 'id' => $a->id, 'title' => $a->title, 'subtitle' => 'Baza wiedzy', 'url' => route('tenant.manager.kb.show', $a)];
        }

        return $results;
    }
}
