<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationHistorySeeder extends Seeder
{
    public function run(): void
    {
        $evaluations = DB::table('evaluations')->get();

        $types = DB::table('evaluation_types as et')
            ->join('evaluation_configs as ec', 'ec.id', '=', 'et.evaluation_config_id')
            ->select('et.*', 'ec.name as config_name')
            ->get();

        if ($evaluations->isEmpty() || $types->isEmpty()) {
            $this->command?->warn('EvaluationHistorySeeder: missing evaluations or evaluation types — run ReportingSeeder and EvaluationConfigSeeder first.');
            return;
        }

        $rows = 0;

        // Use the first 3 types to represent score breakdown per evaluation
        $typeSlice = $types->take(3)->values();

        foreach ($evaluations as $eval) {
            if (DB::table('evaluation_histories')->where('evaluation_id', $eval->id)->exists()) {
                continue;
            }

            $remaining = $eval->total_score;
            $count     = $typeSlice->count();

            foreach ($typeSlice as $idx => $type) {
                $isLast     = ($idx === $count - 1);
                $typeMax    = max(1, (int) $type->score_value);
                $portion    = $isLast
                    ? $remaining
                    : (int) round($remaining / ($count - $idx) * (rand(80, 120) / 100));
                $scoreGiven = max(0, min($portion, $typeMax));
                $remaining -= $scoreGiven;

                DB::table('evaluation_histories')->insert([
                    'evaluation_id'    => $eval->id,
                    'course_online_id' => $eval->course_online_id,
                    'config_name'      => $type->config_name,
                    'type_name'        => $type->type_name,
                    'score_given'      => $scoreGiven,
                    'max_score'        => $typeMax,
                    'created_at'       => now()->subDays(rand(1, 10)),
                    'updated_at'       => now()->subDays(rand(1, 10)),
                ]);
                $rows++;
            }
        }

        $this->command?->info("EvaluationHistorySeeder: {$rows} history rows for {$evaluations->count()} evaluations.");
    }
}
