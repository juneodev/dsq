<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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

        $query = \App\Models\Item::with('itemable')
            ->where('board_id', $board->id)
            ->where('user_id', Auth::id());

        // Folder scope via query param `f`
        $folderUuid = $request->query('f');
        if ($folderUuid === null || $folderUuid === '') {
            // Root items (not inside any folder)
            $query->whereNull('folder_id');
        } else {
            $folderId = Folder::where('uuid', $folderUuid)->value('id');
            if ($folderId) {
                $query->where('folder_id', $folderId);
            } else {
                // If folder not found, return empty results
                $query->whereRaw('1 = 0');
            }
        }

        $items = $query->get();

        return \App\Http\Resources\ItemResource::collection($items);
    }

    /**
     * Render the Board page by UUID.
     */
    public function show(string $uuid)
    {
        // The Vue page (resources/js/pages/Board.vue) expects a `uuid` prop.
        return Inertia::render('Board', ['uuid' => $uuid]);
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
