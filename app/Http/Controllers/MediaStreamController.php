<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsRangedFiles;
use App\Models\Audio;
use App\Models\ModuleContent;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaStreamController extends Controller
{
    use StreamsRangedFiles;

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

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'PDF file not found.');
        }

        $fullPath = Storage::disk('public')->path($path);
        $mimeType = 'application/pdf';
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, $mimeType, $size);
    }

    public function streamVideoQuality(Request $request, int $qualityId): StreamedResponse
    {
        $quality = \App\Models\VideoQuality::findOrFail($qualityId);

        if (!$quality->file_path || !Storage::disk('local')->exists($quality->file_path)) {
            abort(404, 'Quality file not found.');
        }

        $fullPath = Storage::disk('local')->path($quality->file_path);
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, 'video/mp4', $size);
    }

    public function streamSubtitle(int $contentId): \Illuminate\Http\Response
    {
        $content = ModuleContent::where('id', $contentId)
            ->where('content_type', 'video')
            ->with('video')
            ->firstOrFail();

        $video = $content->video;

        abort_unless(
            $video && $video->subtitle_vtt_path && Storage::disk('local')->exists($video->subtitle_vtt_path),
            404,
            'Subtitle file not found.'
        );

        return response(Storage::disk('local')->get($video->subtitle_vtt_path), 200, [
            'Content-Type'        => 'text/vtt; charset=UTF-8',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function streamVideoForTranscode(Request $request, int $videoId): StreamedResponse
    {
        $video = \App\Models\Video::query()->findOrFail($videoId);

        abort_unless(
            $video->file_path && Storage::disk('local')->exists($video->file_path),
            404,
            'Video file not found.'
        );

        $fullPath = Storage::disk('local')->path($video->file_path);
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, 'video/mp4', $size);
    }

    public function streamBlogVideoSubtitle(int $videoId): \Illuminate\Http\Response
    {
        $video = Video::query()->findOrFail($videoId);

        abort_unless(
            $video->subtitle_vtt_path && Storage::disk('local')->exists($video->subtitle_vtt_path),
            404,
            'Subtitle file not found.'
        );

        return response(Storage::disk('local')->get($video->subtitle_vtt_path), 200, [
            'Content-Type'                => 'text/vtt; charset=UTF-8',
            'Content-Disposition'         => 'inline',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function streamBlogVideo(Request $request, int $videoId): StreamedResponse
    {
        $video = Video::query()->findOrFail($videoId);

        abort_unless(
            $video->file_path && Storage::disk('local')->exists($video->file_path),
            404,
            'Video file not found.'
        );

        $fullPath = Storage::disk('local')->path($video->file_path);
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, 'video/mp4', $size);
    }

    public function streamBlogAudio(Request $request, int $audioId): StreamedResponse
    {
        $audio = Audio::query()->findOrFail($audioId);

        abort_unless(
            $audio->local_path && Storage::disk('local')->exists($audio->local_path),
            404,
            'Audio file not found.'
        );

        $fullPath = Storage::disk('local')->path($audio->local_path);
        $size     = filesize($fullPath);

        return $this->streamWithRangeSupport($request, $fullPath, 'audio/mpeg', $size);
    }
}
