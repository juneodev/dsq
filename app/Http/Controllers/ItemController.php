<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Todo;
use App\Models\Checklist;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Item::with('itemable')->where('user_id', Auth::id())->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'x' => $item->x,
                'y' => $item->y,
                'width' => $item->width,
                'height' => $item->height,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                ...$item->itemable->except('id')
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:todo,checklist,folder',
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30',
            // Type-specific fields
            'title' => 'required_if:type,todo,checklist|string|max:255',
            'name' => 'required_if:type,folder|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'items' => 'array', // For checklists
            'color' => 'string' // For folders
        ]);

        // Create the specific model first
        $itemable = $this->createItemableModel($validated['type'], $validated);

        // Create the item with polymorphic relationship
        $item = Item::create([
            'user_id' => Auth::id(),
            'itemable_type' => get_class($itemable),
            'itemable_id' => $itemable->id,
            'x' => $validated['x'] ?? 0,
            'y' => $validated['y'] ?? 0,
            'width' => $validated['width'] ?? 200,
            'height' => $validated['height'] ?? 100
        ]);

        return $item->load('itemable');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return $item->load('itemable');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30',
            // Type-specific fields
            'title' => 'string|max:255',
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'items' => 'array',
            'color' => 'string'
        ]);

        // Update positioning data in Item
        $item->update(collect($validated)->only(['x', 'y', 'width', 'height'])->toArray());

        // Update type-specific data in itemable model
        $itemableData = collect($validated)->except(['x', 'y', 'width', 'height'])->filter()->toArray();
        if (!empty($itemableData)) {
            $item->itemable->update($itemableData);
        }

        return $item->load('itemable');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->itemable->delete(); // Delete the specific model first
        $item->delete(); // Then delete the item
        return response()->json(['message' => 'Item deleted successfully']);
    }

    /**
     * Create the appropriate itemable model based on type.
     */
    private function createItemableModel(string $type, array $data)
    {
        switch ($type) {
            case 'todo':
                return Todo::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'completed' => $data['completed'] ?? false
                ]);
            case 'checklist':
                return Checklist::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'items' => $data['items'] ?? []
                ]);
            case 'folder':
                return Folder::create([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'color' => $data['color'] ?? '#3b82f6'
                ]);
            default:
                throw new \InvalidArgumentException("Invalid item type: {$type}");
        }
    }
}
