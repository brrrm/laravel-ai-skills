<?php

namespace brrrm\AiSkills\Mcp;

use brrrm\AiSkills\Exceptions\SkillNotFoundException;
use brrrm\AiSkills\SkillRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

/**
 * MCP resource template exposing a single skill's SKILL.md body. The {name}
 * variable is bound from the URI by laravel/mcp and made available on the
 * request.
 */
#[MimeType('text/markdown')]
#[Description('Returns the full SKILL.md body of an Agent Skill by name. URI: skills://skill/{name}.')]
class SkillBodyResource extends Resource implements HasUriTemplate
{
    public function __construct(protected SkillRegistry $registry) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('skills://skill/{name}');
    }

    public function handle(Request $request): Response
    {
        $name = (string) $request->get('name', '');

        if ($name === '') {
            return Response::error('Missing skill name in URI.');
        }

        try {
            $skill = $this->registry->find($name);
        } catch (SkillNotFoundException) {
            return Response::error("Skill [{$name}] not found.");
        }

        return Response::text($skill->body());
    }
}
