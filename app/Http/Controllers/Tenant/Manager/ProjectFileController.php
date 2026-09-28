<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectFile;
use App\Models\Tenant\ProjectFileFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectFileController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $folderId = $request->integer('folder_id') ?: null;

        $folders = $project->fileFolders()
            ->where('parent_id', $folderId)
            ->orderBy('name')
            ->get();

        $files = $project->files()
            ->where('folder_id', $folderId)
            ->with('uploader')
            ->latest()
            ->get();

        $breadcrumbs = [];
        if ($folderId) {
            $folder = ProjectFileFolder::findOrFail($folderId);
            $breadcrumbs[] = ['id' => $folder->id, 'name' => $folder->name];
        }

        return Inertia::render('Tenant/Manager/Projects/Files/Index', [
            'project' => $project,
            'folders' => $folders,
            'files' => $files,
            'folderId' => $folderId,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:102400', // 100MB per file
            'folder_id' => 'nullable|exists:project_file_folders,id',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("projects/{$project->id}/files", 'public');

            $project->files()->create([
                'folder_id' => $request->folder_id,
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }

        return back()->with('success', count($request->file('files')) . ' plik(ów) zostało przesłanych.');
    }

    public function destroy(Project $project, ProjectFile $file)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $file->uploaded_by === $user->id, 403);
        abort_unless($file->project_id === $project->id, 404);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', __('messages.file_deleted'));
    }

    public function createFolder(Request $request, Project $project)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:project_file_folders,id',
        ]);

        $project->fileFolders()->create(array_merge($validated, ['created_by' => $user->id]));

        return back()->with('success', __('messages.folder_created'));
    }

    public function download(Project $project, ProjectFile $file)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user->isAdmin() || $project->members->contains($user->id), 403);
        abort_unless($file->project_id === $project->id, 404);

        return Storage::disk('public')->download($file->path, $file->name);
    }
}
