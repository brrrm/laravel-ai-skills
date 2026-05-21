<?php

namespace CalqDev\AiSkills\Exceptions;

use RuntimeException;

class SkillNotFoundException extends RuntimeException
{
    public static function named(string $name): self
    {
        return new self("Skill [{$name}] not found in registered paths.");
    }
}
