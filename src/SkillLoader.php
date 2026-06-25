<?php

namespace brrrm\AiSkills;

/**
 * Walks registered directories and yields the SKILL.md paths of valid skill
 * candidates. Parsing/validation is deferred to the SkillRegistry so that a
 * single malformed skill can be isolated from the rest of the discovery.
 */
class SkillLoader
{
    /**
     * @return iterable<string> Absolute paths to candidate SKILL.md files.
     */
    public function candidatesIn(string $path): iterable
    {
        if (! is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $dir = $path.DIRECTORY_SEPARATOR.$entry;
            $skillMd = $dir.DIRECTORY_SEPARATOR.'SKILL.md';

            if (is_dir($dir) && is_file($skillMd)) {
                yield $skillMd;
            }
        }
    }
}
