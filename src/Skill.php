<?php

namespace CalqDev\AiSkills;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;

/**
 * Immutable representation of a single Agent Skill on disk.
 *
 * The body is loaded lazily from the filesystem to honor progressive
 * disclosure: only metadata is held in memory at registry-boot time.
 */
class Skill
{
    /**
     * @param  array<string, mixed>  $frontmatter
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $directory,
        public readonly string $skillMdPath,
        public readonly array $frontmatter,
    ) {}

    public static function fromFile(string $skillMdPath): self
    {
        if (! is_file($skillMdPath)) {
            throw InvalidSkillException::forPath($skillMdPath, 'file does not exist.');
        }

        $contents = file_get_contents($skillMdPath);
        if ($contents === false) {
            throw InvalidSkillException::forPath($skillMdPath, 'unable to read file.');
        }

        $directory = dirname($skillMdPath);
        $directoryName = basename($directory);

        [$frontmatter, $_] = SkillManifest::split($contents, $skillMdPath);
        SkillManifest::validate($frontmatter, $directoryName, $skillMdPath);

        return new self(
            name: $frontmatter['name'],
            description: $frontmatter['description'],
            directory: $directory,
            skillMdPath: $skillMdPath,
            frontmatter: $frontmatter,
        );
    }

    /**
     * Read the SKILL.md body (the markdown content after the frontmatter).
     */
    public function body(): string
    {
        $contents = file_get_contents($this->skillMdPath);
        if ($contents === false) {
            throw InvalidSkillException::forPath($this->skillMdPath, 'unable to read file.');
        }

        [, $body] = SkillManifest::split($contents, $this->skillMdPath);

        return $body;
    }

    public function license(): ?string
    {
        $v = $this->frontmatter['license'] ?? null;

        return is_string($v) ? $v : null;
    }

    public function compatibility(): ?string
    {
        $v = $this->frontmatter['compatibility'] ?? null;

        return is_string($v) ? $v : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $v = $this->frontmatter['metadata'] ?? [];

        return is_array($v) ? $v : [];
    }
}
