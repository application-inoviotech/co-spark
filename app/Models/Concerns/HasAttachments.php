<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Store uploaded files on the private disk, grouped by form field.
     *
     * @param  array<string, UploadedFile[]>  $filesByField
     */
    public function storeAttachments(array $filesByField, string $directory): void
    {
        foreach ($filesByField as $field => $files) {
            foreach ($files as $file) {
                $this->attachments()->create([
                    'field' => $field,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $file->store("{$directory}/{$this->getKey()}"),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }
    }
}
