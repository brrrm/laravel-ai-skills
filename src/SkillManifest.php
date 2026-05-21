<?php

namespace CalqDev\AiSkills;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use Symfony\Component\Yaml\Yaml;

/**
 * Validates and parses a SKILL.md file's YAML frontmatter against the
 * Agent Skills open standard (https://agentskills.io/specification).
 */
class SkillManifest
{
    public const NAME_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    public const NAME_MAX = 64;

    public const DESCRIPTION_MAX = 1024;

    public const COMPATIBILITY_MAX = 500;

    /**
     * Parse a SKILL.md file's raw contents into [frontmatter, body].
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    public static function split(string $contents, string $path): array
    {
        if (! str_starts_with($contents, '---')) {
            throw InvalidSkillException::forPath($path, 'missing YAML frontmatter (must start with "---").');
        }

        $rest = substr($contents, 3);
        $end = strpos($rest, "\n---");

        if ($end === false) {
            throw InvalidSkillException::forPath($path, 'unterminated YAML frontmatter (closing "---" not found).');
        }

        $yaml = ltrim(substr($rest, 0, $end), "\n");
        $body = ltrim(substr($rest, $end + 4), "\n");

        try {
            $parsed = Yaml::parse($yaml) ?? [];
        } catch (\Throwable $e) {
            throw InvalidSkillException::forPath($path, 'invalid YAML: '.$e->getMessage());
        }

        if (! is_array($parsed)) {
            throw InvalidSkillException::forPath($path, 'frontmatter must be a YAML mapping.');
        }

        return [$parsed, $body];
    }

    /**
     * Validate a parsed frontmatter array against the spec.
     *
     * @param  array<string, mixed>  $frontmatter
     */
    public static function validate(array $frontmatter, string $directoryName, string $path): void
    {
        $name = $frontmatter['name'] ?? null;

        if (! is_string($name) || $name === '') {
            throw InvalidSkillException::forPath($path, '"name" is required and must be a non-empty string.');
        }

        if (strlen($name) > self::NAME_MAX) {
            throw InvalidSkillException::forPath($path, sprintf('"name" exceeds %d characters.', self::NAME_MAX));
        }

        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw InvalidSkillException::forPath($path, '"name" must be lowercase alphanumeric with single hyphens; no leading, trailing or consecutive hyphens.');
        }

        if ($name !== $directoryName) {
            throw InvalidSkillException::forPath($path, sprintf('"name" (%s) must match the parent directory name (%s).', $name, $directoryName));
        }

        $description = $frontmatter['description'] ?? null;

        if (! is_string($description) || $description === '') {
            throw InvalidSkillException::forPath($path, '"description" is required and must be a non-empty string.');
        }

        if (strlen($description) > self::DESCRIPTION_MAX) {
            throw InvalidSkillException::forPath($path, sprintf('"description" exceeds %d characters.', self::DESCRIPTION_MAX));
        }

        if (isset($frontmatter['compatibility'])) {
            $compat = $frontmatter['compatibility'];
            if (! is_string($compat) || strlen($compat) > self::COMPATIBILITY_MAX) {
                throw InvalidSkillException::forPath($path, sprintf('"compatibility" must be a string of at most %d characters.', self::COMPATIBILITY_MAX));
            }
        }

        if (isset($frontmatter['license']) && ! is_string($frontmatter['license'])) {
            throw InvalidSkillException::forPath($path, '"license" must be a string.');
        }

        if (isset($frontmatter['metadata']) && ! is_array($frontmatter['metadata'])) {
            throw InvalidSkillException::forPath($path, '"metadata" must be a mapping.');
        }

        if (isset($frontmatter['allowed-tools']) && ! is_string($frontmatter['allowed-tools'])) {
            throw InvalidSkillException::forPath($path, '"allowed-tools" must be a space-separated string.');
        }
    }
}
