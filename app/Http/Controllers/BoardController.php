<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Board;
use App\Models\Document;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    /**
     * Return the list of boards for the authenticated user.
     */
    public function index(Request $request)
    {
        $boards = Board::query()
            ->where('owner_id', Auth::id())
            ->latest('updated_at')
            ->get(['id', 'uuid', 'title', 'description', 'updated_at', 'created_at']);

        return response()->json($boards);
    }

    /**
     * Store a newly created board for the authenticated user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $board = Board::create([
            'owner_id' => Auth::id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json($board->only(['id', 'uuid', 'title', 'description', 'created_at', 'updated_at']), 201);
    }

    /**
     * Update the specified board (must belong to the authenticated user).
     */
    public function update(Request $request, int $boardId)
    {
        $board = Board::where('owner_id', Auth::id())->findOrFail($boardId);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $board->update($data);

        return response()->json($board->only(['id', 'uuid', 'title', 'description', 'created_at', 'updated_at']));
    }

    /**
     * List items of a given board UUID for the authenticated user.
     */
    public function itemsByUuid(Request $request, string $uuid)
    {
        $board = Board::where('uuid', $uuid)->where('owner_id', Auth::id())->firstOrFail();

        $items = Item::with('itemable')
            ->where('board_id', $board->id)
            ->where('user_id', Auth::id())
            ->get();

        return ItemResource::collection($items);
    }

    /**
     * Upload a file to the given board and create a Document item for it.
     */
    public function upload(Request $request, string $uuid)
    {
        $board = Board::where('uuid', $uuid)->where('owner_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $uploadedFile = $validated['file'];

        // Create Document first (title from original name without extension)
        $document = Document::create([
            'title' => pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'description' => null,
            'url' => null,
        ]);

        // Attach file to media library collection
        $media = $document
            ->addMediaFromRequest('file')
            ->toMediaCollection('documents');

        // Persist a direct accessible URL for current frontend compatibility
        $document->url = $media->getUrl();
        $document->save();

        // Create Item linked to this document
        $item = Item::create([
            'user_id' => Auth::id(),
            'board_id' => $board->id,
            'itemable_type' => Document::class,
            'itemable_id' => $document->id,
            'x' => 20,
            'y' => 20,
            'width' => 320,
            'height' => 120,
        ]);

        return new ItemResource($item->load('itemable'));
    }

    /**
     * Remove the specified board (must belong to the authenticated user).
     */
    public function destroy(Request $request, int $boardId)
    {
        $board = Board::where('owner_id', Auth::id())->findOrFail($boardId);
        $board->delete();

        return response()->json(['message' => 'Board deleted']);
    }
}
