<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\Mcp\SkillBodyResource;
use CalqDev\AiSkills\Mcp\SkillCatalogResource;
use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use CalqDev\AiSkills\Tests\TestCase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class McpResourcesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(SkillRegistry::class, function () {
            return (new SkillRegistry(new SkillLoader))
                ->path(__DIR__.'/../Fixtures/skills');
        });
    }

    public function test_catalog_resource_lists_all_skills(): void
    {
        $resource = $this->app->make(SkillCatalogResource::class);

        $response = $resource->handle(new Request);

        $rendered = $this->renderResponse($response);

        $this->assertStringContainsString('# Skills', $rendered);
        $this->assertStringContainsString('valid-skill', $rendered);
        $this->assertStringContainsString('another-skill', $rendered);
    }

    public function test_catalog_resource_uri(): void
    {
        $resource = $this->app->make(SkillCatalogResource::class);

        $this->assertSame('skills://catalog', $resource->uri());
        $this->assertSame('text/markdown', $resource->mimeType());
    }

    public function test_body_resource_returns_skill_body(): void
    {
        $resource = $this->app->make(SkillBodyResource::class);

        $request = new Request;
        $request->merge(['name' => 'valid-skill']);

        $rendered = $this->renderResponse($resource->handle($request));

        $this->assertStringStartsWith('# Valid Skill', $rendered);
    }

    public function test_body_resource_returns_error_for_unknown_skill(): void
    {
        $resource = $this->app->make(SkillBodyResource::class);

        $request = new Request;
        $request->merge(['name' => 'nope']);

        $rendered = $this->renderResponse($resource->handle($request));

        $this->assertStringContainsString('not found', $rendered);
    }

    public function test_body_resource_uri_template(): void
    {
        $resource = $this->app->make(SkillBodyResource::class);

        $this->assertSame('skills://skill/{name}', (string) $resource->uriTemplate());
    }

    protected function renderResponse(Response $response): string
    {
        return (string) ($response->content()->toArray()['text'] ?? '');
    }
}
