<?php

namespace GptHelperForLaravel\Support;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SplFileInfo;

class ClassNameResolver
{
    /**
     * Resolve a class name to a file path.
     *
     * @param  string  $fullName
     * @return string
     * @throws \Exception
     */
    public function resolveOrFail(string $fullName): string
    {
        $resolved = $this->resolve($fullName);
        if ($resolved === null) {
            throw new \Exception("Could not resolve class name: $fullName");
        }
        return $resolved;
    }

    public function resolve(string $fullName): ?string
    {
        // if it has .php, then it's a file path
        if (str_contains($fullName, '.php')) {
            return $fullName;
        }

        $basePath = app_path();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath), RecursiveIteratorIterator::SELF_FIRST);

        $hasNamespace = false;
        $parts = [];
        // Handle backslashes in the class name
        if (str_contains($fullName, '\\')) {
            $hasNamespace = true;
            $parts = explode('\\', $fullName);
            $className = (string) end($parts);
            array_pop($parts); // Remove the class name from the parts
        // Handle forward slashes
        } elseif (str_contains($fullName, '/')) {
            $hasNamespace = true;
            $parts = explode('/', $fullName);
            $className = (string) end($parts);
            array_pop($parts); // Remove the class name from the parts
        } else {
            $className = $fullName;
        }

        /**
         * helps IDEs to understand the type of $file
         * @var SplFileInfo $file
         */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Get the class name from the filename, it should be the same but without the .php extension
                $path = $file->getPathname();
                $classNameFromFile = $file->getBasename('.php');
                if ($classNameFromFile === $className) {
                    if ( !$hasNamespace) {
                        return $path;
                    }

                    // If the full name has a namespace, then we need to check we have the parts of the namespace
                    if ($this->checkPartsAreInPath($parts, $path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check that all the parts are in the path
     * For use when the full name has a namespace, maybe we have multiple classes with the same name
     *
     * @param  array  $parts
     * @param  string  $path
     * @return bool
     */
    public function checkPartsAreInPath(array $parts, string $path): bool
    {
        $path = strtolower($path);
        $pathParts = explode('/', $path);
        // Remove blank parts and anything before "app"
        $pathParts = array_slice($pathParts, array_search('app', $pathParts));

        foreach ($parts as $part) {
            $part = strtolower($part);
            if (!in_array($part, $pathParts)) {
                return false;
            }
        }
        return true;
    }
}