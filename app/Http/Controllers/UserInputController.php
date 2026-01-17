<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserInputRequest;
use App\Models\UserInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserInputController extends Controller
{
    /**
     * Show the form for creating a new user input.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('user-inputs.create');
    }

    /**
     * Store a newly created user input in storage.
     *
     * @param  \App\Http\Requests\StoreUserInputRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUserInputRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $userInput = UserInput::create($validated);

        return redirect()
            ->route('user-inputs.success', ['user_input' => $userInput->id])
            ->with('success', 'User input has been successfully saved.');
    }

    /**
     * Display the specified user input.
     *
     * @param  \App\Models\UserInput  $userInput
     * @return \Illuminate\View\View
     */
    public function show(UserInput $userInput): View
    {
        return view('user-inputs.show', compact('userInput'));
    }
}
