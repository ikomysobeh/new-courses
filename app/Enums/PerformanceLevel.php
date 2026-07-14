<?php

namespace App\Enums;

class PerformanceLevel
{
    const OUTSTANDING    = 1;
    const RELIABLE       = 2;
    const DEVELOPING     = 3;
    const UNDERPERFORMING = 4;

    // Score ranges on the 0-100 scale: [min, max]. Higher score = better.
    private static array $ranges = [
        self::OUTSTANDING     => [85, 100],
        self::RELIABLE        => [70, 84],
        self::DEVELOPING      => [50, 69],
        self::UNDERPERFORMING => [0,  49],
    ];

    private static array $labels = [
        self::OUTSTANDING     => 'Outstanding',
        self::RELIABLE        => 'Reliable',
        self::DEVELOPING      => 'Developing',
        self::UNDERPERFORMING => 'Underperforming',
    ];

    private static array $colors = [
        self::OUTSTANDING     => '#4CAF50',
        self::RELIABLE        => '#2196F3',
        self::DEVELOPING      => '#FF9800',
        self::UNDERPERFORMING => '#F44336',
    ];

    /**
     * Derive performance level integer from a raw total score.
     */
    public static function getLevelByScore(int $score): int
    {
        foreach (self::$ranges as $level => [$min, $max]) {
            if ($score >= $min && $score <= $max) {
                return $level;
            }
        }
        return self::UNDERPERFORMING;
    }

    /**
     * Human-readable label for a level constant.
     */
    public static function getLabelByLevel(int $level): string
    {
        return self::$labels[$level] ?? 'Unknown';
    }

    /**
     * Score range [min, max] for a level constant.
     */
    public static function getRangeByLevel(int $level): array
    {
        return self::$ranges[$level] ?? [0, 0];
    }

    /**
     * Hex colour for a level constant.
     */
    public static function getColorByLevel(int $level): string
    {
        return self::$colors[$level] ?? '#9E9E9E';
    }

    /**
     * Full level metadata object for frontend consumption.
     */
    public static function getMetaByLevel(int $level): array
    {
        [$min, $max] = self::getRangeByLevel($level);
        return [
            'level' => $level,
            'label' => self::getLabelByLevel($level),
            'color' => self::getColorByLevel($level),
            'range' => ['min' => $min, 'max' => $max],
        ];
    }

    /**
     * All levels with full metadata — useful for frontend dropdowns / legend.
     */
    public static function getForFrontend(): array
    {
        return array_map(
            fn(int $l) => self::getMetaByLevel($l),
            [self::OUTSTANDING, self::RELIABLE, self::DEVELOPING, self::UNDERPERFORMING]
        );
    }
}
