<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Todo::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30'
        ]);

        return Todo::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(Todo $todo)
    {
        return $todo;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30'
        ]);

        $todo->update($validated);
        return $todo;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Todo $todo)
    {
        $todo->delete();
        return response()->json(['message' => 'Todo deleted successfully']);
    }
}
