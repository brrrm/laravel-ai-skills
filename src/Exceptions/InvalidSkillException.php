<?php

namespace CalqDev\AiSkills\Exceptions;

use RuntimeException;

class InvalidSkillException extends RuntimeException
{
    public static function forPath(string $path, string $reason): self
    {
        return new self("Invalid skill at [{$path}]: {$reason}");
    }
}
