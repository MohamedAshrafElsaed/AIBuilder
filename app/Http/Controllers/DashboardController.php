<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = $user->projects()
            ->latest()
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'repo_full_name' => $project->repo_full_name,
                'default_branch' => $project->default_branch,
                'status' => $project->status,
                'created_at' => $project->created_at->toISOString(),
            ]);

        $newProjectId = $request->session()->get('new_project_id');
        $request->session()->forget('new_project_id');

        return Inertia::render('Dashboard', [
            'projects' => $projects,
            'newProjectId' => $newProjectId,
            'hasGitHubToken' => $user->hasGitHubToken(),
        ]);
    }
}
