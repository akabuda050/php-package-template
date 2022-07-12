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
        use {$this->namespace}\Console\Install{$this->name}Command;

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

    public function installCommand()
    {
        return <<<EOL
        <?php

        namespace {$this->namespace}\Console;

        use Illuminate\Console\Command;
        use Illuminate\Support\Facades\File;

        class Install{$this->name}Command extends Command
        {
            protected \$signature = '{$this->configName}:install';

            protected \$description = 'Install the {$this->name}';

            public function handle(): void
            {
                \$this->info('Installing {$this->name}...');

                \$this->info('Publishing configuration...');

                if (! \$this->configExists('{$this->configName}.php')) {
                    \$this->publishConfiguration();
                    \$this->info('Published configuration');
                } else {
                    if (\$this->shouldOverwriteConfig()) {
                        \$this->info('Overwriting configuration file...');
                        \$this->publishConfiguration(true);
                    } else {
                        \$this->info('Existing configuration was not overwritten');
                    }
                }

                \$this->info('Installed {$this->name}');
            }

            private function configExists(string \$fileName): bool
            {
                return File::exists(config_path(\$fileName));
            }

            private function shouldOverwriteConfig(): bool
            {
                return \$this->confirm(
                    'Config file already exists. Do you want to overwrite it?',
                    false
                );
            }

            private function publishConfiguration(bool \$forcePublish = false): void
            {
                \$params = [
                    '--tag' => "{$this->configName}-config"
                ];

                if (\$forcePublish === true) {
                    \$params['--force'] = true;
                }

                \$this->call('vendor:publish', \$params);
            }
        }
        EOL;
    }

    public function testCase()
    {
        return <<<EOL
        <?php

        namespace {$this->namespace}\Tests;

        use \Orchestra\Testbench\TestCase as BaseTestCase;

        class TestCase extends BaseTestCase
        {
            public function setUp(): void
            {
                parent::setUp();
                // additional setup
            }

            protected function getPackageProviders(\$app)
            {
                return [];
            }

            protected function getEnvironmentSetUp(\$app)
            {
                // perform environment setup
            }
        }
        EOL;
    }
    
}
