<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Helpers;

use Symfony\Component\Process\PhpExecutableFinder;

class PhpHelper
{
    /**
     * Under php-fpm `php` from PATH can be missing or be another PHP version,
     * so the binary is taken from the config or found next to the running PHP.
     */
    public function getBinary(): string
    {
        $binary = config('chronos.php_binary');

        if (is_string($binary) && $binary !== '') {
            return $binary;
        }

        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }
}
