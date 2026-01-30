<?php

/*
 * Copyright (c) 2023 Sura
 *
 *  For the full copyright and license information, please view the LICENSE
 *   file that was distributed with this source code.
 *
 */

namespace Sura\Filesystem;

use Sura\Contracts\Filesystem\FilesystemInterface;
use function floor;
use function sprintf;
use function strlen;

/**
 *
 */
class Filesystem implements FilesystemInterface
{
    /**
     * Create dir
     * @param string $dir
     * @param int $mode
     * @return bool
     */
    public static function createDir(string $dir, int $mode = 0777): bool
    {
        return !(!is_dir($dir) && !mkdir($dir, $mode, true) && !is_dir($dir));
    }

    /**
     * Delete file OR directory
     * @param string $file
     * @return bool
     */
    public static function delete(string $file): bool
    {
        if (is_dir($file)) {
            if (!str_ends_with($file, '/')) {
                $file .= '/';
            }
            $files = glob($file . '*', GLOB_MARK);
            foreach ((array)$files as $file_) {
                if (is_string($file_)) {
                    self::delete($file_);
                }
            }
            rmdir($file);
            return true;
        }
        if (is_file($file)) {
            unlink($file);
            return true;
        }
        return false;
    }

    /**
     * Check file or dir
     * @param string $file
     * @return bool
     */
    public static function check(string $file): bool
    {
        return is_file($file) || is_dir($file);
    }

    /**
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function copy(string $from, string $to): bool
    {
        if (is_file($from) && !is_file($to)) {
            return copy($from, $to);
        }
        return false;
    }

    /**
     * size dir
     * @param string $directory
     * @return int
     */
    public static function dirSize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $size += $item->getSize();
            }
        }

        return $size;
    }

    /**
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function humanFileSize(int $bytes, int $decimals = 1): string
    {
        if ($bytes === 0) {
            return '0B';
        }

        $unit = (int) floor(log($bytes, 1024));
        $units = 'BKMGTP';
        $prefix = $units[$unit] ?? 'B';
        $size = $bytes / (1024 ** $unit);

        return sprintf("%.{$decimals}f", $size) . ($prefix === 'B' ? 'B' : "{$prefix}B");
    }
}
