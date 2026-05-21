<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\Anthropic\SkillUploader;
use CalqDev\AiSkills\Skill;
use CalqDev\AiSkills\Tests\TestCase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

class SkillUploaderTest extends TestCase
{
    public function test_uploads_skill_with_all_files_to_anthropic_endpoint(): void
    {
        Http::fake([
            SkillUploader::ENDPOINT => Http::response([
                'id' => 'skill_01ABC',
                'latest_version' => ['version' => '1759178010641129'],
            ], 200),
        ]);

        $skill = Skill::fromFile(__DIR__.'/../Fixtures/skills/valid-skill/SKILL.md');
        $uploader = new SkillUploader(app(Factory::class), 'sk-test-key');

        $response = $uploader->upload($skill, 'Valid Skill Display');

        $this->assertSame('skill_01ABC', $response['id']);

        Http::assertSent(function ($request) {
            $this->assertSame(SkillUploader::ENDPOINT, $request->url());
            $this->assertSame('sk-test-key', $request->header('x-api-key')[0]);
            $this->assertSame(SkillUploader::BETA_HEADER, $request->header('anthropic-beta')[0]);

            $data = collect($request->data());
            $this->assertTrue($data->contains(fn ($part) => ($part['name'] ?? null) === 'display_title'));
            $this->assertTrue($data->contains(fn ($part) => ($part['name'] ?? null) === 'files[]'
                && str_ends_with((string) ($part['filename'] ?? ''), 'valid-skill/SKILL.md')));

            return true;
        });
    }

    public function test_throws_on_failed_upload(): void
    {
        Http::fake([
            SkillUploader::ENDPOINT => Http::response(['error' => 'invalid_request'], 400),
        ]);

        $skill = Skill::fromFile(__DIR__.'/../Fixtures/skills/valid-skill/SKILL.md');
        $uploader = new SkillUploader(app(Factory::class), 'sk-test-key');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/HTTP 400/');

        $uploader->upload($skill);
    }
}
