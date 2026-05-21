<?php

namespace CalqDev\AiSkills\Anthropic;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\Skill;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;

/**
 * Uploads a local skill directory to Anthropic via POST /v1/skills so it can
 * be referenced as container.skills[].type=custom in Messages API requests.
 *
 * @see https://platform.claude.com/docs/en/build-with-claude/skills-guide
 */
class SkillUploader
{
    public const ENDPOINT = 'https://api.anthropic.com/v1/skills';

    public const BETA_HEADER = 'skills-2025-10-02';

    public const MAX_BUNDLE_BYTES = 30 * 1024 * 1024; // 30 MB per Anthropic spec

    public function __construct(
        protected HttpFactory $http,
        protected string $apiKey,
        protected string $apiVersion = '2023-06-01',
    ) {}

    /**
     * Upload a skill. Returns the parsed JSON response body (containing the
     * generated skill_id and version) on success; throws on HTTP failure.
     *
     * @param  string|null  $displayTitle  Optional human-readable title shown in the Anthropic console.
     * @return array<string, mixed>
     */
    public function upload(Skill $skill, ?string $displayTitle = null): array
    {
        $files = $this->collectFiles($skill);

        $totalSize = array_sum(array_map(fn (array $f) => filesize($f['absolute']), $files));
        if ($totalSize > self::MAX_BUNDLE_BYTES) {
            throw new InvalidSkillException(sprintf(
                'Skill bundle for [%s] is %d bytes; Anthropic limit is %d bytes.',
                $skill->name,
                $totalSize,
                self::MAX_BUNDLE_BYTES,
            ));
        }

        $request = $this->http
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'anthropic-beta' => self::BETA_HEADER,
            ])
            ->asMultipart();

        $multipart = [];

        if ($displayTitle !== null) {
            $multipart[] = ['name' => 'display_title', 'contents' => $displayTitle];
        }

        foreach ($files as $file) {
            $multipart[] = [
                'name' => 'files[]',
                'contents' => fopen($file['absolute'], 'r'),
                'filename' => $file['filename'],
            ];
        }

        $response = $request->post(self::ENDPOINT, $multipart);

        $this->assertSuccessful($response, $skill);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return $json;
    }

    /**
     * Walk the skill directory and produce the file list for the multipart
     * upload. Each file is keyed with a path relative to the skill name.
     *
     * @return list<array{absolute: string, filename: string}>
     */
    protected function collectFiles(Skill $skill): array
    {
        $base = realpath($skill->directory);
        if ($base === false) {
            throw new InvalidSkillException("Skill directory does not exist: {$skill->directory}");
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $relative = ltrim(substr($fileInfo->getPathname(), strlen($base)), DIRECTORY_SEPARATOR);
            // The API expects all paths to be rooted at the skill directory name.
            $files[] = [
                'absolute' => $fileInfo->getPathname(),
                'filename' => $skill->name.DIRECTORY_SEPARATOR.$relative,
            ];
        }

        return $files;
    }

    protected function assertSuccessful(Response $response, Skill $skill): void
    {
        if ($response->successful()) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Anthropic skill upload for [%s] failed with HTTP %d: %s',
            $skill->name,
            $response->status(),
            $response->body(),
        ));
    }
}
