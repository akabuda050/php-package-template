<?php

namespace JsonBaby\Installer;

use Nette\PhpGenerator\PhpNamespace;

class Installer
{
    private $steps = [
        'promptStub',
        'promptPackageName',
        'promptPackageDescription',
        'promptAuthorName',
        'promptAuthorEmail',
        'generateNamespace',
        'generateStub'
    ];

    private $packageName;
    private $packageDescription;
    private $authorName;
    private $authorEmail;
    private $namespace;

    public function run()
    {
        foreach ($this->steps as $step) {
            if (method_exists($this, $step)) {
                call_user_func([$this, $step]);
            }
        }
    }


    public function promptStub()
    {
        $this->stub = $this->readlineTerminal('Enter type (php or laravel): ');
    }

    public function promptPackageName()
    {
        $this->packageName = $this->readlineTerminal('Enter package name (vendor/my-package): ');
    }

    public function promptPackageDescription()
    {
        $this->packageDescription = $this->readlineTerminal('Enter package name (vendor/my-package): ');
    }

    public function promptAuthorName()
    {
        $this->authorName = $this->readlineTerminal('Enter your name: ');
    }

    public function promptAuthorEmail()
    {
        $this->authorEmail = $this->readlineTerminal('Enter your email: ');
    }

    public function generateNamespace()
    {
        $packageName = $this->packageName;
        if (!$packageName || strpos($packageName, '/') === false) {
            $this->namespace = 'ExampleNameSpace\\\\';
        } else {
            $namespace = array_map(
                static function ($part): string {
                    $part = preg_replace('/[^a-z0-9]/i', ' ', $part);
                    $part = ucwords($part);

                    return str_replace(' ', '', $part);
                },
                explode('/', $packageName)
            );

            $this->namespace = implode('\\\\', $namespace) . '\\\\';
        }
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $file
     * @return void
     */
    protected function replaceInFile(string $search, string $replace, string $file)
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }

    public function readlineTerminal($prompt = '')
    {
        $prompt && print $prompt;
        $terminal_device = '/dev/tty';
        $h = fopen($terminal_device, 'r');
        if ($h === false) {
            return false; # probably not running in a terminal.
        }
        $line = rtrim(fgets($h), "\r\n");
        fclose($h);
        return $line;
    }

    public function generateStub()
    {
        switch ($this->stub) {
            case 'php':
                $this->generatePhpStub();
                break;
            case 'laravel':
                break;
        }
    }

    public function generatePhpStub()
    {
        $namespace = str_replace('\\\\', '\\', $this->namespace);
        if (str_ends_with($namespace, '\\')) {
            $namespace = rtrim($namespace, '\\');
        }
        $namespace = new PhpNamespace($namespace);
        $class = $namespace->addClass('Foo');
        $method = $class->addMethod('__construct');
        $method->addPromotedParameter('bar');

        echo <<<EOL
        <?php

        $namespace

        EOL;
        
        echo getcwd();
    }

    public function generateLaravelStub()
    {
    }


    public function composerJsonStub()
    {
        file_put_contents('stubs/composer.json', <<<EOL
        {
            "name": "{$this->packageName}",
            "description": "{$this->packageDescription}",
            "type": "package",
            "require": {
                "php": "^8.0.2"
            },
            "require-dev": {
                "phpunit/phpunit": "^9.5.8",
                "vimeo/psalm": "5.x-dev"
            },
            "license": "MIT",
            "authors": [
                {
                    "name": "{$this->authorName}",
                    "email": "{$this->authorEmail}"
                }
            ],
            "autoload": {
                "psr-4": {
                    "{$this->namespace}": "src"
                }
            },
            "autoload-dev": {
                "psr-4": {
                    "{$this->namespace}Tests": "tests"
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
            "minimum-stability": "dev",
            "prefer-stable": true
        }

        EOL);
    }

    private function recursiveCopy($src, $dst)
    {
        

        $dir = opendir($src);
        @mkdir($dst);
        while (($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
