<?php

namespace CalqDev\AiSkills;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\Exceptions\SkillNotFoundException;
use Illuminate\Support\Collection;

/**
 * Central registry of Agent Skills. Paths are registered eagerly (typically in
 * a ServiceProvider's boot()) and skills are discovered lazily on first access.
 */
class SkillRegistry
{
    /** @var list<string> */
    protected array $paths = [];

    /** @var Collection<string, Skill>|null */
    protected ?Collection $skills = null;

    /** @var list<InvalidSkillException> */
    protected array $errors = [];

    protected bool $strict = false;

    public function __construct(protected SkillLoader $loader) {}

    /**
     * Register a directory to scan for skills.
     */
    public function path(string $path): self
    {
        $this->paths[] = $path;
        $this->skills = null; // invalidate cache

        return $this;
    }

    /**
     * @param  iterable<string>  $paths
     */
    public function paths(iterable $paths): self
    {
        foreach ($paths as $path) {
            $this->path($path);
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function registeredPaths(): array
    {
        return $this->paths;
    }

    /**
     * Strict mode rethrows InvalidSkillException; otherwise invalid skills
     * are collected and accessible via errors().
     */
    public function strict(bool $strict = true): self
    {
        $this->strict = $strict;
        $this->skills = null;

        return $this;
    }

    /**
     * @return Collection<string, Skill>
     */
    public function all(): Collection
    {
        return $this->skills ??= $this->discover();
    }

    public function find(string $name): Skill
    {
        $skill = $this->all()->get($name);

        if (! $skill instanceof Skill) {
            throw SkillNotFoundException::named($name);
        }

        return $skill;
    }

    public function has(string $name): bool
    {
        return $this->all()->has($name);
    }

    /**
     * @return list<InvalidSkillException>
     */
    public function errors(): array
    {
        $this->all();

        return $this->errors;
    }

    public function flush(): self
    {
        $this->skills = null;
        $this->errors = [];

        return $this;
    }

    /**
     * @return Collection<string, Skill>
     */
    protected function discover(): Collection
    {
        $this->errors = [];
        $skills = new Collection;

        foreach ($this->paths as $path) {
            foreach ($this->loader->candidatesIn($path) as $skillMdPath) {
                try {
                    $skill = Skill::fromFile($skillMdPath);
                } catch (InvalidSkillException $e) {
                    if ($this->strict) {
                        throw $e;
                    }
                    $this->errors[] = $e;

                    continue;
                }

                if ($skills->has($skill->name)) {
                    // Earlier paths win — matches Laravel's view/lang override semantics.
                    continue;
                }

                $skills->put($skill->name, $skill);
            }
        }

        return $skills;
    }
}
