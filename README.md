# laravel-ai-skills

Agent Skills support for the [Laravel AI SDK](https://github.com/laravel/ai).

This package adds support for the [Agent Skills](https://agentskills.io/specification) open standard
(originated by Anthropic, October 2025) to `laravel/ai`. Skills are filesystem-resident `SKILL.md`
directories containing YAML frontmatter plus Markdown instructions, loaded into your agent's context
via *progressive disclosure*: metadata is always available, full instructions load only when the LLM
decides a skill matches the task.

Skills written for Claude Code (e.g. those in [`anthropics/skills`](https://github.com/anthropics/skills))
work in Laravel agents without modification.

## Status

MVP — vendor-agnostic loader works with any `laravel/ai` provider. Anthropic's native
`container.skills[]` API is supported as an opt-in path.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/ai` ^0.7

## Installation

```bash
composer require calq-dev/laravel-ai-skills
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=ai-skills-config
```

## Usage

### 1. Create a skill

```bash
php artisan ai:skill:make pdf-extractor \
    --description="Extract text and tables from PDF files. Use when working with PDF documents."
```

This scaffolds `.claude/skills/pdf-extractor/SKILL.md`. Edit the body with instructions, examples,
and references to bundled scripts or templates.

### 2. Add the trait to an agent

```php
use CalqDev\AiSkills\Concerns\HasSkills;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;

class DocumentAgent implements Agent, HasTools
{
    use HasSkills;

    public function instructions(): string
    {
        return $this->withSkillCatalog('You are a helpful document-processing assistant.');
    }

    public function tools(): iterable
    {
        return $this->skillTools();
    }
}
```

The agent's system prompt now includes an `## Available Skills` block listing every discovered skill.
When the LLM sees a skill that matches the task, it calls the `load_skill` tool to retrieve the full
SKILL.md body.

### 3. Limit which skills a specific agent sees

```php
protected function skillNames(): ?array
{
    return ['pdf-extractor', 'csv-analyzer'];
}
```

## Anthropic native skills (opt-in)

Anthropic's Messages API supports a [native Skills feature](https://platform.claude.com/docs/en/build-with-claude/skills-guide)
that attaches pre-built or custom skills via `container.skills[]`. Use the `HasNativeSkills` trait
plus `HasProviderOptions` to wire this:

```php
use CalqDev\AiSkills\Anthropic\AnthropicSkills;
use CalqDev\AiSkills\Anthropic\HasNativeSkills;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;

class PresentationAgent implements Agent, HasProviderOptions
{
    use HasNativeSkills;

    protected function anthropicSkills(): array
    {
        return [
            AnthropicSkills::entry('pptx'),
            AnthropicSkills::entry('xlsx'),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        if ($provider === Lab::Anthropic) {
            return $this->anthropicSkillsProviderOptions();
        }
        return [];
    }
}
```

Set the required beta headers on the Anthropic provider's `anthropic_beta` config:

```php
// config/ai.php
'anthropic' => [
    'anthropic_beta' => \CalqDev\AiSkills\Anthropic\AnthropicSkills::BETA_HEADERS,
],
```

## Configuration

```php
// config/ai-skills.php
return [
    'paths' => [
        base_path('.claude/skills'),
        resource_path('skills'),
    ],
    'strict' => env('AI_SKILLS_STRICT', false),
];
```

Register additional paths programmatically from another package's `ServiceProvider::boot()`:

```php
\CalqDev\AiSkills\Facades\Skills::path(__DIR__.'/../skills');
```

## Artisan commands

| Command | Purpose |
|---|---|
| `ai:skill:list` | List all discovered skills with their paths |
| `ai:skill:validate` | Validate every SKILL.md against the spec |
| `ai:skill:make {name}` | Scaffold a new SKILL.md from the stub |
| `ai:skill:push {name}` | Upload a skill as a custom skill to Anthropic |

## Pushing skills to Anthropic

Skills can be uploaded to Anthropic as custom container skills, then referenced
in Messages API requests via `container.skills[].skill_id`.

```bash
ANTHROPIC_API_KEY=sk-... php artisan ai:skill:push pdf-extractor \
    --title="PDF Extractor"
```

The command bundles every file under the skill directory (subject to Anthropic's
30 MB limit) and returns the generated `skill_id` plus version timestamp.

## MCP bridge (optional)

If your app uses [`laravel/mcp`](https://github.com/laravel/mcp), you can expose
skills as MCP resources so external clients (Claude Code, Cursor, etc.) can
discover and read them:

```php
// app/Mcp/Servers/YourServer.php
use CalqDev\AiSkills\Mcp\SkillBodyResource;
use CalqDev\AiSkills\Mcp\SkillCatalogResource;
use Laravel\Mcp\Server;

class YourServer extends Server
{
    protected array $resources = [
        SkillCatalogResource::class,
        SkillBodyResource::class,
    ];
}
```

- `skills://catalog` — markdown list of every registered skill
- `skills://skill/{name}` — full SKILL.md body of a single skill

## Dev hot-reload

By default, the registry re-scans the filesystem on every `all()` call when the
app is **not** in production. This means edits to SKILL.md files are picked up
immediately under Octane, queue workers, and other long-running processes
without a restart. Override via the `AI_SKILLS_AUTO_FLUSH` env var.

## SKILL.md format

Follows the [Agent Skills spec](https://agentskills.io/specification) exactly:

```yaml
---
name: my-skill                # required, [a-z0-9-]+, ≤64 chars, matches directory
description: One-liner.       # required, ≤1024 chars
license: MIT                  # optional
compatibility: Requires Node. # optional, ≤500 chars
metadata:                     # optional
  author: me
allowed-tools: Read Bash      # optional, experimental
---

# Markdown body — loaded only when the LLM triggers this skill via load_skill.
```

Anthropic extensions (`when_to_use`, `model`, `paths`, etc.) are parsed tolerantly into the
frontmatter map without validation errors.

## Security

Skills can execute code and reference external resources. Treat installing a third-party skill
like installing a package: audit `SKILL.md`, any scripts under `scripts/`, and references in
`references/` before trusting it. The `allowed-tools` field is advisory — the SDK's existing
permission model still governs tool execution.

## License

MIT
