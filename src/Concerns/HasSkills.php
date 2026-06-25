<?php

namespace brrrm\AiSkills\Concerns;

use brrrm\AiSkills\Skill;
use brrrm\AiSkills\SkillRegistry;
use brrrm\AiSkills\Tools\LoadSkillTool;
use Illuminate\Support\Collection;

/**
 * Mix this trait into a Laravel\Ai Agent to give it Skills support.
 *
 * The agent calls $this->withSkillCatalog($instructions) inside its own
 * instructions() method, and $this->skillTools() (optionally merged with
 * other tools) inside its tools() method. The agent class must implement
 * Laravel\Ai\Contracts\HasTools for the load_skill tool to be picked up.
 */
trait HasSkills
{
    /**
     * Limit which skills this agent sees. Override to return:
     *   - null: every registered skill is available (default)
     *   - array<string>: only skills with these names
     *
     * @return array<int, string>|null
     */
    protected function skillNames(): ?array
    {
        return null;
    }

    /**
     * @return Collection<string, Skill>
     */
    public function availableSkills(): Collection
    {
        $all = app(SkillRegistry::class)->all();
        $filter = $this->skillNames();

        if ($filter === null) {
            return $all;
        }

        return $all->only($filter);
    }

    /**
     * Append the skill catalog to a base instruction string. Returns the
     * input unchanged when no skills are registered.
     */
    public function withSkillCatalog(string $instructions): string
    {
        $catalog = $this->skillCatalog();

        return $catalog === '' ? $instructions : $instructions.PHP_EOL.PHP_EOL.$catalog;
    }

    /**
     * Render the catalog block injected into the system prompt.
     */
    public function skillCatalog(): string
    {
        $skills = $this->availableSkills();

        if ($skills->isEmpty()) {
            return '';
        }

        $lines = $skills->map(
            fn (Skill $skill) => "- **{$skill->name}**: {$skill->description}"
        )->values()->all();

        return "## Available Skills\n\n"
            .implode("\n", $lines)
            ."\n\nWhen one of these skills matches the current task, call the `load_skill` tool "
            .'with its `name` to retrieve the full instructions before acting.';
    }

    /**
     * @return array<int, LoadSkillTool>
     */
    public function skillTools(): array
    {
        if ($this->availableSkills()->isEmpty()) {
            return [];
        }

        return [app(LoadSkillTool::class)];
    }
}
