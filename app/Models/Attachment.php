<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['attachable_type', 'attachable_id', 'path', 'filename', 'uploaded_by'])]
class Attachment extends Model
{
    /** Files live on the private local disk, under attachments/{project_id}/. */
    public const DISK = 'local';

    public static function directoryFor(int $projectId): string
    {
        return "attachments/{$projectId}";
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
