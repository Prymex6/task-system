<?php

namespace App\Services;

use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectTemplate;
use App\Models\Tenant\User;

class ProjectTemplateService
{
    public static function createFromProject(Project $project, string $name, User $by): ProjectTemplate
    {
        $template = ProjectTemplate::create([
            'name' => $name,
            'created_by' => $by->id,
        ]);

        foreach ($project->tasks()->whereNull('parent_task_id')->get() as $task) {
            $template->tasks()->create([
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'estimated_hours' => $task->estimated_hours,
                'story_points' => $task->story_points,
            ]);
        }

        return $template;
    }

    public static function applyToProject(ProjectTemplate $template, Project $project, User $by): void
    {
        foreach ($template->tasks as $templateTask) {
            $project->tasks()->create([
                'title' => $templateTask->title,
                'description' => $templateTask->description,
                'priority' => $templateTask->priority,
                'estimated_hours' => $templateTask->estimated_hours,
                'story_points' => $templateTask->story_points,
                'created_by' => $by->id,
            ]);
        }
    }
}
