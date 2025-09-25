<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $itemable = $this->whenLoaded('itemable');
        $type = strtolower(class_basename($this->itemable_type));

        $base = [
            'id' => $this->id,
            'type' => $this->type, // accessor on model
            'x' => (int) $this->x,
            'y' => (int) $this->y,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        $specific = [];
        if ($itemable) {
            switch ($type) {
                case 'todo':
                    $specific = [
                        'title' => $itemable->title,
                        'description' => $itemable->description,
                        'completed' => (bool) ($itemable->completed ?? false),
                    ];
                    break;
                case 'checklist':
                    $specific = [
                        'title' => $itemable->title,
                        'description' => $itemable->description,
                        'items' => $itemable->items ?? [],
                    ];
                    break;
                case 'folder':
                    $specific = [
                        'uuid' => $itemable->uuid,
                        'name' => $itemable->name,
                        'description' => $itemable->description,
                        'color' => $itemable->color,
                    ];
                    break;
                case 'note':
                    $specific = [
                        'title' => $itemable->title,
                        'content' => $itemable->content,
                        'color' => $itemable->color,
                        'pinned' => (bool) ($itemable->pinned ?? false),
                    ];
                    break;
                case 'bookmark':
                    $specific = [
                        'title' => $itemable->title,
                        'url' => $itemable->url,
                        'favicon_url' => $itemable->favicon_url,
                        'tags' => $itemable->tags ?? [],
                    ];
                    break;
                case 'event':
                    $specific = [
                        'title' => $itemable->title,
                        'start_at' => $itemable->start_at,
                        'end_at' => $itemable->end_at,
                        'location' => $itemable->location,
                        'all_day' => (bool) ($itemable->all_day ?? false),
                        'remind_minutes_before' => $itemable->remind_minutes_before,
                    ];
                    break;
                default:
                    // Unknown type: expose nothing extra
                    $specific = [];
                    break;
            }
        }

        return array_merge($base, $specific);
    }
}
