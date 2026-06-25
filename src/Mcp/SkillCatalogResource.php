<?php

namespace brrrm\AiSkills\Mcp;

use brrrm\AiSkills\Skill;
use brrrm\AiSkills\SkillRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

/**
 * MCP resource exposing the skill catalog (name + description for each skill).
 * External MCP clients (Claude Code, Cursor, etc.) can fetch this and decide
 * which skill bodies to load next via the body-resource template.
 */
#[Uri('skills://catalog')]
#[MimeType('text/markdown')]
#[Description('List of every Agent Skill registered in this Laravel app, with name and description for each.')]
class SkillCatalogResource extends Resource
{
    public function __construct(protected SkillRegistry $registry) {}

    public function handle(Request $request): Response
    {
        $skills = $this->registry->all();

        if ($skills->isEmpty()) {
            return Response::text("# Skills\n\nNo skills registered.\n");
        }

        $lines = $skills->map(
            fn (Skill $skill) => "- **{$skill->name}**: {$skill->description}"
        )->values()->all();

        $body = "# Skills\n\n"
            .implode("\n", $lines)
            ."\n\nFetch a skill body via `skills://skill/{name}`.";

        return Response::text($body);
    }
}
