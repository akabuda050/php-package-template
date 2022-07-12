<?php

namespace JsonBaby\Installer;

class ComposerJsonGenerator
{
    public function __construct(
        private $packageName,
        private $packageDescription,
        private $authorName,
        private $authorEmail,
        private $namespace,
        private $stub
    ) {
    }

    public function generate()
    {
        return <<<EOL
        {
            "name": "{$this->packageName}",
            "description": "{$this->packageDescription}",
            "type": "package",
            "require": {
                {$this->generateRequire()}
            },
            "require-dev": {
                {$this->generateRequireDev()}
            },
            "license": "MIT",
            {$this->generateAuthorSection()}
            "autoload": {
                "psr-4": {
                    "{$this->namespace}\\\\": "src"
                }
            },
            "autoload-dev": {
                "psr-4": {
                    "{$this->namespace}\\\\Tests": "tests"
                }
            },
            "scripts": {
                "psalm": "vendor/bin/psalm",
                "test": "vendor/bin/phpunit",
                "test-coverage": "vendor/bin/phpunit --coverage-html coverage"
            },
            "config": {
                "sort-packages": true
            },
            {$this->generateLaravelExtra()}
            "minimum-stability": "dev",
            "prefer-stable": true
        }

        EOL;
    }

    protected function generateRequire()
    {
        if ($this->stub === 'laravel') {
            return <<<EOL
            "php": "^8.0.2",
                    "illuminate/support": "^9.0"
            EOL;
        }

        return <<<EOL
        "php": "^8.0.2"
        EOL;
    }

    protected function generateRequireDev()
    {
        if ($this->stub === 'laravel') {
            return <<<EOL
            "orchestra/testbench": "^7.1",
                    "phpunit/phpunit": "^9.5.8",
                    "vimeo/psalm": "5.x-dev"
            EOL;
        }
        return <<<EOL
            "phpunit/phpunit": "^9.5.8",
                    "vimeo/psalm": "5.x-dev"
            EOL;
    }

    protected function generateLaravelExtra()
    {
        if ($this->stub === 'laravel') {
            return <<<EOL
            "extra": {
                    "laravel": {
                        "providers": [
                            "{$this->namespace}\\\\{$this->generateServiceProviderName()}ServiceProvider"
                        ]
                    }
                },
            EOL;
        }
        return '';
    }

    protected function generateAuthorSection()
    {
        if ($this->authorName && !$this->authorEmail) {
            return <<<EOL
            "authors": [
                    {
                        "name": "{$this->authorName}",
                    }
                ],
            EOL;
        }
        if ($this->authorEmail && !$this->authorName) {
            return <<<EOL
            "authors": [
                    {
                        "email": "{$this->authorEmail}"
                    }
                ],
            EOL;
        }
        if ($this->authorEmail && $this->authorName) {
            return <<<EOL
            "authors": [
                    {
                        "name": "{$this->authorName}",
                        "email": "{$this->authorEmail}"
                    }
                ],
            EOL;
        }
        return '';
    }

    public function generateServiceProviderName()
    {
        $packageName = $this->packageName;
        if (!$packageName || strpos($packageName, '/') === false) {
            return 'ExamplePackage';
        } else {
            $namespace = array_map(
                static function ($part): string {
                    $part = preg_replace('/[^a-z0-9]/i', '-', $part);
                    $parts = array_map(
                        static function ($part): string {
                            $part = ucwords($part);
                            return $part;
                        },
                        explode('-', $part)
                    );

                    return implode('', $parts);
                },
                explode('/', $packageName)
            );

            return "$namespace[1]";
        }
    }
}
