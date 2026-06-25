<?php

namespace brrrm\AiSkills;

use brrrm\AiSkills\Console\ListSkillsCommand;
use brrrm\AiSkills\Console\MakeSkillCommand;
use brrrm\AiSkills\Console\PushSkillCommand;
use brrrm\AiSkills\Console\ValidateSkillCommand;
use Illuminate\Support\ServiceProvider;

class AiSkillsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-skills.php', 'ai-skills');

        $this->app->singleton(SkillLoader::class);

        $this->app->singleton(SkillRegistry::class, function ($app) {
            $registry = new SkillRegistry($app->make(SkillLoader::class));

            $registry->strict((bool) $app['config']->get('ai-skills.strict', false));
            $registry->autoFlush((bool) $app['config']->get('ai-skills.auto_flush', false));
            $registry->paths($app['config']->get('ai-skills.paths', []));

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ai-skills.php' => config_path('ai-skills.php'),
        ], 'ai-skills-config');

        $this->publishes([
            __DIR__.'/../stubs/skill.md.stub' => base_path('stubs/ai-skill.md.stub'),
        ], 'ai-skills-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListSkillsCommand::class,
                ValidateSkillCommand::class,
                MakeSkillCommand::class,
                PushSkillCommand::class,
            ]);
        }
    }
}
