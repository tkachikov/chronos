<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Helpers;

use Tkachikov\Memory\Memory;

/**
 * Memory of runs is stored as a string with units (e.g. "512 KB", "24.5 MB").
 */
class MemoryHelper
{
    private const UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PT'];

    public function toBytes(?string $memory): ?float
    {
        if (
            $memory === null
            || !preg_match('/^\s*(\d+(?:\.\d+)?)\s*([A-Z]+)\s*$/i', $memory, $matches)
        ) {
            return null;
        }

        $pow = array_search(strtoupper($matches[2]), self::UNITS, true);

        return $pow === false
            ? null
            : (float) $matches[1] * 1024 ** $pow;
    }

    public function format(float $bytes): string
    {
        return $bytes >= 1
            ? (new Memory())->size($bytes)->show()
            : '0 B';
    }
}
