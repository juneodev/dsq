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
                case 'document':
                    // Generate a signed URL for S3, or a public/local URL otherwise
                    $media = method_exists($itemable, 'getFirstMedia') ? $itemable->getFirstMedia('documents') : null;
                    $url = null;
                    if ($media) {
                        try {
                            $disk = config('media-library.disk_name');
                            $driver = config("filesystems.disks.$disk.driver");
                            if ($driver === 's3' && method_exists($media, 'getTemporaryUrl')) {
                                // Signed URL valid for 10 minutes
                                $url = $media->getTemporaryUrl(now()->addMinutes(10));
                            } else {
                                // Local/public disks
                                $url = $media->getUrl();
                            }
                        } catch (\Throwable $e) {
                            // Fallback to non-signed URL if something goes wrong
                            try {
                                $url = $media->getUrl();
                            } catch (\Throwable $e2) {
                                $url = null;
                            }
                        }
                    }

                    $specific = [
                        'title' => $itemable->title,
                        'description' => $itemable->description,
                        'url' => $url,
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
