<?php

namespace CalqDev\AiSkills\Tests\Unit;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\SkillManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkillManifestTest extends TestCase
{
    public function test_split_parses_frontmatter_and_body(): void
    {
        $contents = "---\nname: my-skill\ndescription: hello\n---\nBody here\n";

        [$fm, $body] = SkillManifest::split($contents, '/x/SKILL.md');

        $this->assertSame('my-skill', $fm['name']);
        $this->assertSame('hello', $fm['description']);
        $this->assertSame("Body here\n", $body);
    }

    public function test_split_rejects_missing_frontmatter(): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/missing YAML frontmatter/');

        SkillManifest::split("# Just a heading\n", '/x/SKILL.md');
    }

    public function test_split_rejects_unterminated_frontmatter(): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/unterminated/');

        SkillManifest::split("---\nname: x\ndescription: y\nbody never closes", '/x/SKILL.md');
    }

    public function test_validate_passes_minimal_valid_frontmatter(): void
    {
        $this->expectNotToPerformAssertions();

        SkillManifest::validate(
            ['name' => 'my-skill', 'description' => 'desc'],
            'my-skill',
            '/x/SKILL.md'
        );
    }

    #[DataProvider('invalidNames')]
    public function test_validate_rejects_invalid_names(string $name, string $expectedMessageFragment): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/'.preg_quote($expectedMessageFragment, '/').'/');

        SkillManifest::validate(
            ['name' => $name, 'description' => 'desc'],
            $name,
            '/x/SKILL.md'
        );
    }

    public static function invalidNames(): array
    {
        return [
            'uppercase' => ['Bad-Name', 'lowercase'],
            'leading hyphen' => ['-foo', 'lowercase'],
            'trailing hyphen' => ['foo-', 'lowercase'],
            'consecutive hyphens' => ['foo--bar', 'lowercase'],
            'underscore' => ['foo_bar', 'lowercase'],
            'space' => ['foo bar', 'lowercase'],
        ];
    }

    public function test_validate_rejects_name_too_long(): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/exceeds 64/');

        $longName = str_repeat('a', 65);
        SkillManifest::validate(
            ['name' => $longName, 'description' => 'desc'],
            $longName,
            '/x/SKILL.md'
        );
    }

    public function test_validate_rejects_dir_mismatch(): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/must match the parent directory/');

        SkillManifest::validate(
            ['name' => 'my-skill', 'description' => 'desc'],
            'different-dir',
            '/x/SKILL.md'
        );
    }

    public function test_validate_rejects_missing_description(): void
    {
        $this->expectException(InvalidSkillException::class);

        SkillManifest::validate(['name' => 'my-skill'], 'my-skill', '/x/SKILL.md');
    }

    public function test_validate_rejects_description_too_long(): void
    {
        $this->expectException(InvalidSkillException::class);
        $this->expectExceptionMessageMatches('/1024/');

        SkillManifest::validate(
            ['name' => 'my-skill', 'description' => str_repeat('x', 1025)],
            'my-skill',
            '/x/SKILL.md'
        );
    }

    public function test_validate_rejects_invalid_compatibility(): void
    {
        $this->expectException(InvalidSkillException::class);

        SkillManifest::validate(
            ['name' => 'my-skill', 'description' => 'd', 'compatibility' => str_repeat('a', 501)],
            'my-skill',
            '/x/SKILL.md'
        );
    }
}
