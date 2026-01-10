<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Services\GitHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private GitHubService $github,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasGitHubToken()) {
            return redirect()->route('github.connect');
        }

        return Inertia::render('projects/SelectRepository', [
            'repositories' => fn () => $this->github->getRepositories($user),
        ]);
    }

    public function confirm(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasGitHubToken()) {
            return redirect()->route('github.connect');
        }

        $validated = $request->validate([
            'repo_full_name' => ['required', 'string'],
        ]);

        $repoFullName = $validated['repo_full_name'];

        $existingProject = $user->projects()->where('repo_full_name', $repoFullName)->first();
        if ($existingProject) {
            return redirect()->route('dashboard')
                ->with('error', 'This repository has already been added.');
        }

        $repoDetails = $this->github->getRepository($user, $repoFullName);

        if (! $repoDetails) {
            return redirect()->route('projects.create')
                ->with('error', 'Unable to fetch repository details. Please try again.');
        }

        return Inertia::render('projects/ConfirmRepository', [
            'repository' => $repoDetails,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $project = $user->projects()->create([
            'provider' => 'github',
            'repo_full_name' => $validated['repo_full_name'],
            'repo_id' => $validated['repo_id'] ?? null,
            'default_branch' => $validated['default_branch'],
            'status' => 'processing',
        ]);

        return redirect()->route('dashboard')
            ->with('new_project_id', $project->id);
    }
}
