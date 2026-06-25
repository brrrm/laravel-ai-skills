<?php

namespace brrrm\AiSkills\Anthropic;

/**
 * Marker trait + helper for agents that want to attach skills via Anthropic's
 * native container.skills[] API rather than the vendor-agnostic catalog +
 * load_skill tool pattern.
 *
 * The agent must:
 *   - implement Laravel\Ai\Contracts\HasProviderOptions
 *   - return $this->anthropicSkillsProviderOptions() from providerOptions()
 *     when the provider is Lab::Anthropic
 *
 * Skills are referenced by their Anthropic skill_id (pre-built: 'pptx',
 * 'xlsx', 'docx', 'pdf'; custom: 'skill_01...'). Custom-skill upload is
 * out of MVP scope.
 */
trait HasNativeSkills
{
    /**
     * Override to declare which Anthropic skills attach to this agent.
     *
     * @return array<int, array{type?: string, skill_id: string, version?: string}>
     */
    protected function anthropicSkills(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function anthropicSkillsProviderOptions(): array
    {
        $skills = $this->anthropicSkills();

        if ($skills === []) {
            return [];
        }

        return [
            'container' => [
                'skills' => array_values(array_map(
                    static function (array $skill): array {
                        return [
                            'type' => $skill['type'] ?? 'anthropic',
                            'skill_id' => $skill['skill_id'],
                            'version' => $skill['version'] ?? 'latest',
                        ];
                    },
                    $skills
                )),
            ],
        ];
    }
}
