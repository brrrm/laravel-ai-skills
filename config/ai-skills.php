<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Skill Discovery Paths
    |--------------------------------------------------------------------------
    |
    | Directories scanned for SKILL.md files at registry-boot time. Each
    | direct subdirectory containing a SKILL.md is treated as a skill.
    |
    | Earlier paths win on name collision (same semantics as
    | View::addNamespace). Use Skills::path() from another ServiceProvider's
    | boot() to register additional paths programmatically.
    |
    */

    'paths' => [
        base_path('.claude/skills'),
        resource_path('skills'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Validation
    |--------------------------------------------------------------------------
    |
    | When true, an invalid SKILL.md throws InvalidSkillException during
    | discovery. When false, the registry collects errors silently and you
    | can inspect them via Skills::errors().
    |
    */

    'strict' => env('AI_SKILLS_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | Auto-flush (dev hot-reload)
    |--------------------------------------------------------------------------
    |
    | When true, the registry re-scans the filesystem on every all() call so
    | that edits to SKILL.md files are picked up without restarting Octane,
    | queue workers, or long-running processes. Defaults to enabled outside
    | of production.
    |
    */

    'auto_flush' => env('AI_SKILLS_AUTO_FLUSH', ! app()->isProduction()),

    /*
    |--------------------------------------------------------------------------
    | Anthropic Native Container Skills
    |--------------------------------------------------------------------------
    |
    | Documentation only — opt-in is per agent via the HasNativeSkills trait.
    | Required beta headers when using native skills:
    |   code-execution-2025-08-25,skills-2025-10-02
    | Set these in config('ai.anthropic.anthropic_beta') on the provider.
    |
    */

    'anthropic_beta_headers' => 'code-execution-2025-08-25,skills-2025-10-02',

];
