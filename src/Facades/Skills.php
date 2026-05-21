<?php

namespace CalqDev\AiSkills\Facades;

use CalqDev\AiSkills\SkillRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SkillRegistry path(string $path)
 * @method static SkillRegistry paths(iterable $paths)
 * @method static SkillRegistry strict(bool $strict = true)
 * @method static SkillRegistry autoFlush(bool $enabled = true)
 * @method static Collection all()
 * @method static \CalqDev\AiSkills\Skill find(string $name)
 * @method static bool has(string $name)
 * @method static array errors()
 * @method static SkillRegistry flush()
 * @method static array registeredPaths()
 *
 * @see SkillRegistry
 */
class Skills extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SkillRegistry::class;
    }
}
