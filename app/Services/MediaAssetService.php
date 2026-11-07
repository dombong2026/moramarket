<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MediaAssetService
{
    public function store(UploadedFile $file, User $user, ?string $altText = null): MediaAsset
    {
        $path = $file->store('media/'.$user->id, 'local');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The image could not be saved.');
        }
        try {
            $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));

            return MediaAsset::create([
                'user_id' => $user->id,
                'path' => $path,
                'original_name' => mb_substr($name, 0, 190),
                'alt_text' => $altText,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
