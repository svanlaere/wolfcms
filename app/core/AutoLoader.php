<?php

declare(strict_types=1);

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Autoloader with hybrid support for:
 * - PSR-4
 * - Legacy folder scanning
 * - File class map
 * - Runtime + persistent cache
 * - Skipped paths
 * - Composer integration (priority)
 *
 * PHP 8.3+ syntax and features used where appropriate.
 * Compatible with static analysis (PHPStan, Psalm)
 */
final class AutoLoader
{
    /** @var array<class-string, string> */
    protected static array $classMap = [];

    /** @var array<non-empty-string, string> */
    protected static array $psr4Prefixes = [];

    /** @var list<string> */
    protected static array $legacyFolders = [];

    /** @var array<class-string, string> */
    protected static array $resolvedCache = [];

    /** @var array<string, int> */
    protected static array $fileTimestamps = [];

    /** @var array<string, bool> */
    protected static array $skipPaths = [];

    protected static ?string $cacheFile = null;

    protected static bool $cacheDirty = false;

    public static bool $debug = false;

    public static function register(?string $cacheFile = null): void
    {
        if ($cacheFile !== null) {
            self::$cacheFile = $cacheFile;
            self::loadCache();
            register_shutdown_function(self::saveCache(...));
        }

        // Composer autoloader will remain primary
        spl_autoload_register(self::load(...), prepend: false);
    }

    public static function addPsr4(string $prefix, string $baseDir): void
    {
        $prefix = rtrim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$psr4Prefixes[$prefix] = $baseDir;
    }

    public static function addFile(string|array $class, ?string $file = null): void
    {
        if (is_array($class)) {
            foreach ($class as $cls => $f) {
                self::$classMap[$cls] = $f;
            }
        } elseif ($file !== null) {
            self::$classMap[$class] = $file;
        }
    }

    public static function addFileFromFolder(string $folder): void
    {
        self::$classMap += self::scanFolder($folder);
    }

    public static function addFolder(string|array $folders): void
    {
        foreach ((array)$folders as $folder) {
            self::$legacyFolders[] = $folder;
            self::$classMap += self::scanFolder($folder);
        }
    }

    public static function skipPath(string|array $paths): void
    {
        foreach ((array)$paths as $path) {
            if ($real = realpath($path)) {
                self::$skipPaths[$real] = true;
            }
        }
    }

    public static function load(string $class): void
    {
        if ($path = self::getResolvedPath($class)) {
            require $path;
            return;
        }

        if (self::$debug) {
            error_log("AutoLoader: Class '{$class}' not found.");
        }
    }

    /**
     * Get resolved path for a class.
     *
     * @param class-string $class
     * @return string|null
     */
    public static function getResolvedPath(string $class): ?string
    {
        if (isset(self::$resolvedCache[$class]) && is_file(self::$resolvedCache[$class])) {
            return self::$resolvedCache[$class];
        }

        if (isset(self::$classMap[$class]) && is_file(self::$classMap[$class])) {
            return self::cacheResolved($class, self::$classMap[$class]);
        }

        foreach (self::$psr4Prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
                $fullPath = $baseDir . $relativePath;
                if (is_file($fullPath)) {
                    return self::cacheResolved($class, $fullPath);
                }
            }
        }

        foreach (self::$legacyFolders as $folder) {
            $map = self::scanFolder($folder);
            if (isset($map[$class])) {
                return self::cacheResolved($class, $map[$class]);
            }
        }

        return null;
    }

    /**
     * Scan a directory for PHP classes.
     *
     * @param string $dir
     * @return array<class-string, string>
     */
    protected static function scanFolder(string $dir): array
    {
        $map = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                fn($file) => !self::shouldSkip($file->getRealPath())
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getRealPath();
            $mtime = $file->getMTime();

            if (isset(self::$fileTimestamps[$path]) && self::$fileTimestamps[$path] === $mtime) {
                continue;
            }

            $contents = file_get_contents($path);
            if (!$contents) {
                continue;
            }

            if (preg_match_all('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+(\w+)/mi', $contents, $matches)) {
                $namespace = '';
                if (preg_match('/^\s*(?:\/\/.*\n|\s*)*namespace\s+([^;]+);/mi', $contents, $nsMatch)) {
                    $namespace = trim($nsMatch[1]) . '\\';
                }

                foreach ($matches[1] as $className) {
                    $fqcn = $namespace . $className;
                    $map[$fqcn] = $path;
                }

                self::$fileTimestamps[$path] = $mtime;
            }
        }

        return $map;
    }

    /**
     * Determine if a path should be skipped.
     *
     * @param string|false $path
     * @return bool
     */
    protected static function shouldSkip(string|false $path): bool
    {
        if (!$path) return true;

        foreach (self::$skipPaths as $skip => $_) {
            if (str_starts_with($path, $skip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cache resolved class path.
     *
     * @param class-string $class
     * @param string $path
     * @return string
     */
    protected static function cacheResolved(string $class, string $path): string
    {
        self::$resolvedCache[$class] = $path;
        self::$cacheDirty = true;
        return $path;
    }

    /**
     * Load cache from file.
     */
    protected static function loadCache(): void
    {
        if (!self::$cacheFile || !file_exists(self::$cacheFile)) return;

        $data = json_decode(file_get_contents(self::$cacheFile), true);
        if (is_array($data)) {
            self::$resolvedCache = $data;
        }
    }

    /**
     * Save resolved class cache to file.
     */
    public static function saveCache(): void
    {
        if (!self::$cacheFile || !self::$cacheDirty) return;

        file_put_contents(
            self::$cacheFile,
            json_encode(self::$resolvedCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        self::$cacheDirty = false;
    }

    /**
     * Flush memory and persistent cache.
     */
    public static function flushCache(): void
    {
        self::$resolvedCache = [];
        self::$fileTimestamps = [];
        self::$cacheDirty = true;

        if (self::$cacheFile && file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
}
