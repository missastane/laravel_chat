<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function canDownload(User $user, Media $media)
    {
        return $user->conversations->contains($media->message->conversation_id);
    }
}
