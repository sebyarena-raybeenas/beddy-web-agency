<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends Controller
{
    /** Streams a private attachment; access is limited to authenticated panel users by the route middleware. */
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk(Attachment::DISK);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->filename);
    }
}
