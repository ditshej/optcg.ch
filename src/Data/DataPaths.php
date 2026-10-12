<?php

declare(strict_types=1);

namespace Optcg\Data;

/**
 * Resolves the well-known file and directory locations of the data set
 * relative to a project root (see docs/plan.md section 5).
 */
final class DataPaths
{
    public function __construct(
        public readonly string $rootDir,
    ) {
    }

    public function roomsFile(): string
    {
        return $this->rootDir . '/data/rooms.yaml';
    }

    public function linksDir(): string
    {
        return $this->rootDir . '/data/links';
    }

    public function linksFile(string $roomId): string
    {
        return $this->linksDir() . "/{$roomId}.yaml";
    }

    public function roomsSchemaFile(): string
    {
        return $this->rootDir . '/data/schema/rooms.schema.json';
    }

    public function linksSchemaFile(): string
    {
        return $this->rootDir . '/data/schema/links.schema.json';
    }

    public function iconsDir(): string
    {
        return $this->rootDir . '/public/icons';
    }

    /**
     * Returns $path relative to the root dir, for readable error messages.
     */
    public function relative(string $path): string
    {
        $root = rtrim($this->rootDir, '/') . '/';
        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
