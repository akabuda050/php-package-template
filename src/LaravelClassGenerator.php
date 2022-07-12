<?php

namespace JsonBaby\Installer;

class LaravelClassGenerator
{
    public function __construct(private $name, private $namespace, private $configName)
    {
    }

    public function serviceProvider()
    {
        return <<<EOL
        <?php

        namespace $this->namespace;

        use Illuminate\Support\ServiceProvider;

        class {$this->name}ServiceProvider extends ServiceProvider
        {
            /**
             * Bootstrap the application services.
             */
            public function boot(): void
            {
                if (\$this->app->runningInConsole()) {
                    \$this->publishes([
                        __DIR__ . '/../config/$this->configName.php' => config_path('$this->configName.php'),
                    ], '$this->configName-config');

                    \$this->commands([
                        Install{$this->name}Command::class,
                    ]);
                }
            }

            /**
             * Register the application services.
             */
            public function register(): void
            {
                // Automatically apply the package configuration
                \$this->mergeConfigFrom(__DIR__ . '/../config/$this->configName.php', '$this->configName');
            }
        }

        EOL;
    }
}
