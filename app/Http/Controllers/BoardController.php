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
    public function show(Request $request, string $uuid)
    {
        // Build breadcrumbs including folder hierarchy if provided via query param `f`.
        $board = Board::where('uuid', $uuid)->where('owner_id', Auth::id())->firstOrFail();

        $breadcrumbs = [
            [
                'title' => 'Dashboard',
                'href' => route('dashboard'),
            ],
            [
                'title' => $board->title,
                'href' => route('board', ['uuid' => $board->uuid]),
            ],
        ];

        // If we are inside a folder, append its ancestors and itself to the breadcrumb trail
        $folderUuid = $request->query('f');
        if (!empty($folderUuid)) {
            $trail = [];
            $current = Folder::where('uuid', $folderUuid)->first();
            // Walk up via the polymorphic item relation to find parent folders
            while ($current) {
                $trail[] = [
                    'title' => $current->name,
                    'href' => route('board', ['uuid' => $board->uuid, 'f' => $current->uuid]),
                ];
                // Move to parent folder (if any)
                $parentFolderId = optional($current->item)->folder_id;
                if (!$parentFolderId) {
                    break;
                }
                $current = Folder::find($parentFolderId);
            }
            // We built from current up to root; reverse to get root -> current order
            $breadcrumbs = array_merge($breadcrumbs, array_reverse($trail));
        }

        return Inertia::render('Board', [
            'uuid' => $uuid,
            'breadcrumbs' => $breadcrumbs,
        ]);
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
