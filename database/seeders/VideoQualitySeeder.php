<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VideoQualitySeeder extends Seeder
{
    public function run(): void
    {
        $videos = DB::table('videos')->whereNull('deleted_at')->get();

        if ($videos->isEmpty()) {
            $this->command?->warn('VideoQualitySeeder: no videos found — run VideoSeeder first.');
            return;
        }

        $qualities = [
            ['quality' => '360p',  'factor' => 0.12],
            ['quality' => '720p',  'factor' => 0.45],
            ['quality' => '1080p', 'factor' => 1.00],
        ];

        $rows = 0;

        foreach ($videos as $video) {
            $baseSizeMb = rand(80, 300);

            foreach ($qualities as $q) {
                if (DB::table('video_qualities')
                    ->where('video_id', $video->id)
                    ->where('quality', $q['quality'])
                    ->exists()) {
                    continue;
                }

                DB::table('video_qualities')->insert([
                    'video_id'   => $video->id,
                    'quality'    => $q['quality'],
                    'file_path'  => 'videos/' . $video->id . '/' . $q['quality'] . '/playlist.m3u8',
                    'file_size'  => (int) ($baseSizeMb * 1_000_000 * $q['factor']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $rows++;
            }
        }

        $this->command?->info("VideoQualitySeeder: {$rows} quality variants for {$videos->count()} videos.");
    }
}
