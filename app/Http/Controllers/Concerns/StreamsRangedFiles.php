<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a local file honoring the browser's Range header, streaming the
 * requested byte range in small chunks with an explicit flush() after each
 * one. Plain response()->file() technically supports ranges too, but doesn't
 * reliably push bytes to the browser progressively on this hosting stack -
 * the client ends up waiting for the whole file before playback starts.
 */
trait StreamsRangedFiles
{
    protected function streamWithRangeSupport(
        Request $request,
        string $fullPath,
        string $mimeType,
        int $size
    ): StreamedResponse {
        $start  = 0;
        $end    = $size - 1;
        $status = 200;

        $headers = [
            'Content-Type'   => $mimeType,
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => $size,
        ];

        if ($request->hasHeader('Range')) {
            $range = $request->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);

            $start = (int) $matches[1];
            $end   = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $end   = min($end, $size - 1);
            $length = $end - $start + 1;

            $headers['Content-Length'] = $length;
            $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
            $status = 206;
        }

        return response()->stream(function () use ($fullPath, $start, $end) {
            $handle = fopen($fullPath, 'rb');
            fseek($handle, $start);

            $remaining = $end - $start + 1;
            $chunkSize = 8192;

            while ($remaining > 0 && !feof($handle)) {
                $read   = min($chunkSize, $remaining);
                $buffer = fread($handle, $read);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                flush();
            }

            fclose($handle);
        }, $status, $headers);
    }
}
