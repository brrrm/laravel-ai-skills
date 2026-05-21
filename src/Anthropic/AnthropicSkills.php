<?php

namespace CalqDev\AiSkills\Anthropic;

/**
 * Static helpers and constants for Anthropic's native Skills API.
 */
final class AnthropicSkills
{
    /**
     * Beta headers required by the Skills API. Set these on the Anthropic
     * provider's `anthropic_beta` config when using native skills.
     */
    public const BETA_HEADERS = 'code-execution-2025-08-25,skills-2025-10-02';

    /**
     * Pre-built Anthropic skill IDs available via container.skills[].
     */
    public const PREBUILT = ['pptx', 'xlsx', 'docx', 'pdf'];

    /**
     * Build a container.skills[] entry for a pre-built or custom skill.
     *
     * @return array{type: string, skill_id: string, version: string}
     */
    public static function entry(string $skillId, string $version = 'latest', string $type = 'anthropic'): array
    {
        return [
            'type' => $type,
            'skill_id' => $skillId,
            'version' => $version,
        ];
    }
}
