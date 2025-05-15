<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')), // if sender will be loaded
            'content' => $this->content,
            'message_type' => $this->message_type,
            'sent_at' => $this->sent_at ? $this->sent_at->toDateTimeString() : null,
            'parent_id' => $this->parent_id ?: null,
            'created_at' => $this->created_at->toDateTimeString(),
            'is_edited' => $this->created_at->ne($this->updated_at), // ne => compare time difference between created_at and updated_at. if will be unequal return true : false;
           'media' => $this->media->pluck('file_path')->map(fn($path) => asset('storage/' . str_replace('\\', '/', $path))),

        ];
    }
}
