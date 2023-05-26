<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Helpers;

use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ClassHelper
{
    /**
     * Find class in app directory and create it
     *
     * @param string $searchName class name which need find (Example: CardDtoValidator)
     * @param string $path       directory for search (Example: DtoTransformer - ./app/DtoTransformer)
     *
     * @return mixed
     */
    public static function findAppClass(string $searchName, string $path = ''): mixed
    {
        $files = self::getAllFiles(app_path($path));
        foreach ($files as $file) {
            if ($searchName === $file->getBasename('.php')) {
                return self::createObject($file, $path);
            }
        }

        return null;
    }

    /**
     * @param SplFileInfo $file
     * @param string      $path
     * @param array       ...$params
     *
     * @return mixed
     */
    public static function createObject(SplFileInfo $file, string $path, array ...$params): mixed
    {
        $class = self::toNamespace('App')
            .self::toNamespace($path)
            .self::toNamespace($file->getRelativePath())
            .$file->getBasename('.php');

        return new $class(...$params);
    }

    /**
     * Get all files on path
     *
     * @param string $path
     *
     * @return array
     */
    public static function getAllFiles(string $path): array
    {
        $fileSystem = new FileSystem();

        return $fileSystem->exists($path)
            ? $fileSystem->allFiles($path)
            : [];
    }

    /**
     * Convert part path to class namespace
     *
     * @param string $name
     *
     * @return string
     */
    public static function toNamespace(string $name): string
    {
        $name = str_replace('/', '\\', $name);

        return $name ? $name.'\\' : '';
    }

    /**
     * @param mixed $object
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public static function getValue(mixed $object, string $property): mixed
    {
        if (is_string($object)) {
            $object = new $object;
        }
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);

        try {
            return $property->getValue($object);
        } catch (Throwable) {
            //
        }

        return null;
    }
}
