<?php

namespace App\Services\Video;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VideoChunkUploadService
{
    public function uploadChunk(
        UploadedFile $chunk,
        string $uuid,
        int $index,
        int $total,
        ?string $filename
    ): array {
        $this->assertValidUuid($uuid);

        $dir = 'chunks/' . $uuid;

        Storage::disk('local')->put("{$dir}/chunk_{$index}", file_get_contents($chunk->getRealPath()));

        $received = count(Storage::disk('local')->files($dir));

        if ($received >= $total) {
            $originalFilename = $filename ?? ($index === 0 ? 'video.mp4' : $this->resolveFilename($uuid));
            return $this->finalize($uuid, $originalFilename, $total);
        }

        return [
            'status'   => 'pending',
            'received' => $received,
            'total'    => $total,
        ];
    }

    public function finalize(string $uuid, string $originalFilename, int $total): array
    {
        $this->assertValidUuid($uuid);

        $dir   = 'chunks/' . $uuid;
        $files = Storage::disk('local')->files($dir);

        if (count($files) < $total) {
            throw ValidationException::withMessages([
                'chunk' => "Upload incomplete: expected {$total} chunks, received " . count($files) . '.',
            ]);
        }

        $sanitized   = $this->sanitizeFilename($originalFilename);
        $destination = 'videos/' . $uuid . '_' . $sanitized;

        $destPath = Storage::disk('local')->path($destination);
        $destDir  = dirname($destPath);

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $out = fopen($destPath, 'wb');

        for ($i = 0; $i < $total; $i++) {
            $chunkPath = Storage::disk('local')->path("{$dir}/chunk_{$i}");

            if (!file_exists($chunkPath)) {
                fclose($out);
                throw ValidationException::withMessages([
                    'chunk' => "Chunk {$i} is missing. Cannot finalize upload.",
                ]);
            }

            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        $fileSize = filesize($destPath);

        Storage::disk('local')->deleteDirectory($dir);

        return [
            'status'    => 'complete',
            'file_path' => $destination,
            'file_size' => $fileSize,
        ];
    }

    public function revert(string $uuid): void
    {
        if (!Str::isUuid($uuid)) {
            return;
        }

        Storage::disk('local')->deleteDirectory('chunks/' . $uuid);
    }

    private function assertValidUuid(string $uuid): void
    {
        abort_if(
            !Str::isUuid($uuid),
            422,
            'Invalid upload UUID.'
        );
    }

    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[\/\\\\]/', '', basename($filename));
    }

    private function resolveFilename(string $uuid): string
    {
        Log::warning("VideoChunkUploadService: original_filename missing for uuid={$uuid}, using fallback.");
        return 'video.mp4';
    }
}
