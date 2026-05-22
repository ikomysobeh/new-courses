<?php

namespace App\Http\Controllers;

use App\Models\ModuleContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaStreamController extends Controller
{
    public function streamVideo(Request $request, int $contentId): StreamedResponse
    {
        $content = ModuleContent::where('id', $contentId)
            ->where('content_type', 'video')
            ->firstOrFail();

        $video = $content->video;
        if (!$video) {
            abort(404, 'Video not found.');
        }

        $path = $video->file_path ?? null;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Video file not found.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $mimeType = 'video/mp4';
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, $mimeType, $size);
    }

    public function streamPdf(Request $request, int $contentId): StreamedResponse
    {
        $content = ModuleContent::where('id', $contentId)
            ->where('content_type', 'pdf')
            ->firstOrFail();

        $pdf = $content->pdf;
        if (!$pdf) {
            abort(404, 'PDF not found.');
        }

        $path = $pdf->file_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'PDF file not found.');
        }

        $fullPath = Storage::disk('local')->path($path);
        $mimeType = 'application/pdf';
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, $mimeType, $size);
    }

    private function streamWithRangeSupport(
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
