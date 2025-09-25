<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Todo;
use App\Models\Checklist;
use App\Models\Folder;
use App\Models\Board;
use App\Models\Note;
use App\Models\Bookmark;
use App\Models\Event as CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'type' => 'required|string|in:todo,checklist,folder,note,bookmark,event',
            'board_id' => 'sometimes|integer|exists:boards,id',
            'board_uuid' => 'sometimes|string|exists:boards,uuid',
            'folder_uuid' => 'nullable|uuid',
            'x' => 'integer|min:0',
            'y' => 'integer|min:0',
            'width' => 'integer|min:50',
            'height' => 'integer|min:30',
            // Type-specific fields
            // Title is required for checklist, optional for todo/note/bookmark/event (defaults applied when needed)
            'title' => 'required_if:type,checklist|string|max:255',
            'name' => 'required_if:type,folder|string|max:255',
            'description' => 'nullable|string',
            'completed' => 'boolean',
            'items' => 'array', // For checklists
            'color' => 'string', // For folders OR note background
            // Note
            'content' => 'nullable|string',
            'pinned' => 'boolean',
            // Bookmark
            'url' => 'string',
            'favicon_url' => 'nullable|string',
            'tags' => 'array',
            // Event
            'start_at' => 'date',
            'end_at' => 'nullable|date',
            'location' => 'nullable|string',
            'all_day' => 'boolean',
            'remind_minutes_before' => 'nullable|integer|min:0'
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
        } elseif ($validated['type'] === 'note') {
            $validated['title'] = $validated['title'] ?? 'New Note';
            $validated['content'] = $validated['content'] ?? '';
            $validated['color'] = $validated['color'] ?? '#FEF3C7';
            $validated['pinned'] = $validated['pinned'] ?? false;
            $validated['width'] = $validated['width'] ?? 320;
            $validated['height'] = $validated['height'] ?? 200;
        } elseif ($validated['type'] === 'bookmark') {
            $validated['title'] = $validated['title'] ?? 'New Link';
            $validated['url'] = $validated['url'] ?? 'https://example.com';
            $validated['favicon_url'] = $validated['favicon_url'] ?? null;
            $validated['tags'] = $validated['tags'] ?? [];
            $validated['width'] = $validated['width'] ?? 260;
            $validated['height'] = $validated['height'] ?? 120;
        } elseif ($validated['type'] === 'event') {
            $validated['title'] = $validated['title'] ?? 'New Event';
            $validated['start_at'] = $validated['start_at'] ?? now();
            $validated['end_at'] = $validated['end_at'] ?? null;
            $validated['location'] = $validated['location'] ?? null;
            $validated['all_day'] = $validated['all_day'] ?? false;
            $validated['remind_minutes_before'] = $validated['remind_minutes_before'] ?? null;
            $validated['width'] = $validated['width'] ?? 280;
            $validated['height'] = $validated['height'] ?? 140;
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

        // Resolve folder if provided via UUID
        $folderId = null;
        if (!empty($validated['folder_uuid'] ?? null)) {
            $folderId = \App\Models\Folder::where('uuid', $validated['folder_uuid'])->value('id');
        }

        // Create the item with polymorphic relationship
        $item = Item::create([
            'user_id' => Auth::id(),
            'board_id' => $boardId,
            'folder_id' => $folderId,
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
            'color' => 'string',
            // Note
            'content' => 'nullable|string',
            'pinned' => 'boolean',
            // Bookmark
            'url' => 'string',
            'favicon_url' => 'nullable|string',
            'tags' => 'array',
            // Event
            'start_at' => 'date',
            'end_at' => 'nullable|date',
            'location' => 'nullable|string',
            'all_day' => 'boolean',
            'remind_minutes_before' => 'nullable|integer|min:0',
            // Moving into/out of a folder
            'folder_uuid' => ['nullable', 'uuid']
        ]);

        // Update positioning data in Item
        $item->update(collect($validated)->only(['x', 'y', 'width', 'height'])->toArray());

        // Handle moving item into/out of a folder if requested
        if ($request->has('folder_uuid')) {
            $folderUuid = $validated['folder_uuid'] ?? null;
            if ($folderUuid === null || $folderUuid === '') {
                // Move to root
                $item->folder_id = null;
            } else {
                // Prevent assigning a folder to itself
                $isItemAFolder = strtolower(class_basename($item->itemable_type)) === 'folder';
                $selfFolderUuid = $isItemAFolder ? optional($item->itemable)->uuid : null;

                if ($isItemAFolder && $selfFolderUuid && $selfFolderUuid === $folderUuid) {
                    // Ignore self-assignment; keep folder_id unchanged
                } else {
                    $folderId = Folder::where('uuid', $folderUuid)->value('id');
                    // If not found, keep as-is; alternatively, you could return 422
                    $item->folder_id = $folderId ?? null;
                }
            }
            $item->save();
        }

        // Update type-specific data in itemable model
        // Important: keep boolean false values; filter out only nulls
        $itemableData = collect($validated)
            ->except(['x', 'y', 'width', 'height', 'folder_uuid'])
            ->filter(fn ($v) => $v !== null)
            ->toArray();
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
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'color' => $data['color'] ?? '#3b82f6'
                ]);
            case 'note':
                return Note::create([
                    'title' => $data['title'] ?? 'New Note',
                    'content' => $data['content'] ?? null,
                    'color' => $data['color'] ?? '#FEF3C7',
                    'pinned' => $data['pinned'] ?? false,
                ]);
            case 'bookmark':
                return Bookmark::create([
                    'title' => $data['title'] ?? 'New Link',
                    'url' => $data['url'] ?? 'https://example.com',
                    'favicon_url' => $data['favicon_url'] ?? null,
                    'tags' => $data['tags'] ?? [],
                ]);
            case 'event':
                return CalendarEvent::create([
                    'title' => $data['title'] ?? 'New Event',
                    'start_at' => $data['start_at'] ?? now(),
                    'end_at' => $data['end_at'] ?? null,
                    'location' => $data['location'] ?? null,
                    'all_day' => $data['all_day'] ?? false,
                    'remind_minutes_before' => $data['remind_minutes_before'] ?? null,
                ]);
            default:
                throw new \InvalidArgumentException("Invalid item type: {$type}");
        }
    }
}
