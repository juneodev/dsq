<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Todo;
use App\Models\Checklist;
use App\Models\Folder;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = Item::with('itemable')
            ->where('user_id', Auth::id())
            ->get();

        return ItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:todo,checklist,folder',
            'board_id' => 'sometimes|integer|exists:boards,id',
            'board_uuid' => 'sometimes|string|exists:boards,uuid',
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30',
            // Type-specific fields
            // Title is required for checklist, optional for todo (defaults will be applied server-side)
            'title' => 'required_if:type,checklist|string|max:255',
            'name' => 'required_if:type,folder|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'items' => 'array', // For checklists
            'color' => 'string' // For folders
        ]);

        // Apply server-side defaults for quick-create flows
        if (($validated['type'] ?? null) === 'todo') {
            $validated['title'] = $validated['title'] ?? 'New Todo';
            $validated['description'] = $validated['description'] ?? 'Click to edit description';
            $validated['completed'] = $validated['completed'] ?? false;
            $validated['x'] = $validated['x'] ?? 0;
            $validated['y'] = $validated['y'] ?? 0;
            $validated['width'] = $validated['width'] ?? 350;
            $validated['height'] = $validated['height'] ?? 200;
        }

        // Create the specific model first
        $itemable = $this->createItemableModel($validated['type'], $validated);

        // Resolve target board: prefer provided board_uuid, then board_id, otherwise use user's first board or create a default one
        if (!empty($validated['board_uuid'] ?? null)) {
            $boardId = Board::where('uuid', $validated['board_uuid'])
                ->where('owner_id', Auth::id())
                ->value('id');
        } else {
            $boardId = $validated['board_id'] ?? Board::where('owner_id', Auth::id())->value('id');
        }
        if (!$boardId) {
            $defaultBoard = Board::create([
                'owner_id' => Auth::id(),
                'title' => 'My Board',
                'description' => null,
            ]);
            $boardId = $defaultBoard->id;
        }

        // Create the item with polymorphic relationship
        $item = Item::create([
            'user_id' => Auth::id(),
            'board_id' => $boardId,
            'itemable_type' => get_class($itemable),
            'itemable_id' => $itemable->id,
            'x' => $validated['x'] ?? 0,
            'y' => $validated['y'] ?? 0,
            'width' => $validated['width'] ?? 200,
            'height' => $validated['height'] ?? 100
        ]);

        return new ItemResource($item->load('itemable'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return new ItemResource($item->load('itemable'));
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

        return new ItemResource($item->load('itemable'));
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
