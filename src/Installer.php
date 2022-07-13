<?php

namespace JsonBaby\Installer;

use DirectoryIterator;
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
    private $stub;

    public function run()
    {
        print "Type q on any step to exit.\n";
        foreach ($this->steps as $step) {
            if (method_exists($this, $step)) {
                call_user_func([$this, $step]);
            }
        }
    }


    public function promptStub()
    {
        $stub = $this->readlineTerminal('Enter type (php or laravel): ');
        $this->stub = rtrim(trim($stub)) === '' ? 'php' : $stub;
    }

    public function promptPackageName()
    {
        $packageName = $this->readlineTerminal('Enter package name (vendor/my-package): ');
        $this->packageName = rtrim(trim($packageName)) === '' ? 'vendor/my-package' : $packageName;
    }

    public function promptPackageDescription()
    {
        $this->packageDescription = $this->readlineTerminal('Enter package description: ');
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
            $this->namespace = 'ExampleNameSpace';
        } else {
            $namespace = array_map(
                static function ($part): string {
                    $part = preg_replace('/[^a-z0-9]/i', ' ', $part);
                    $part = ucwords($part);

                    return str_replace(' ', '', $part);
                },
                explode('/', $packageName)
            );

            $this->namespace = implode('\\\\', $namespace);
        }
    }

    public function generateConfigName()
    {
        $packageName = $this->packageName;
        if (!$packageName || strpos($packageName, '/') === false) {
            return 'example-config';
        } else {
            $namespace = array_map(
                static function ($part): string {
                    return preg_replace('/[^a-z0-9]/i', '-', $part);
                },
                explode('/', $packageName)
            );

            return $namespace[1];
        }
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

        if($line === 'q') {
            exit(0);
        }

        return $line;
    }

    public function generateStub()
    {
        $path = getcwd() . '/generated';
        if (is_dir($path)) {
            $this->deleteContent($path);
        }

        switch ($this->stub) {
            case 'php':
                $this->generatePhpStub($path);
                break;
            case 'laravel':
                $this->generateLaravelStub($path);
                break;
        }
    }

    public function generatePhpStub($path)
    {
        $namespace = str_replace('\\\\', '\\', $this->namespace);
        if (str_ends_with($namespace, '\\')) {
            $namespace = rtrim($namespace, '\\');
        }
        $namespace = new PhpNamespace($namespace);
        $class = $namespace->addClass('Foo');
        $method = $class->addMethod('__construct');
        $method->addPromotedParameter('bar');

        $class = <<<EOL
        <?php

        $namespace

        EOL;

        $src = __DIR__ . '/stubs';
        $this->recursiveCopy($src, $path);
        rename($path . '/github', $path . '/.github');
        $this->composerJsonStub($path);

        @mkdir($path . '/src');
        file_put_contents($path . '/src/Foo.php', $class);
    }

    public function generateLaravelStub($path)
    {
        $config = $this->generateConfigName();
        $namespace = str_replace('\\\\', '\\', $this->namespace);
        if (str_ends_with($namespace, '\\')) {
            $namespace = rtrim($namespace, '\\');
        }

        $generalName = $this->generateServiceProviderName();
        $laravelClassGenerator = new LaravelClassGenerator($generalName, $namespace, $config);
        $serviceProvider = $laravelClassGenerator->serviceProvider();

        $src = __DIR__ . '/stubs';
        $this->recursiveCopy($src, $path);
        rename($path . '/github', $path . '/.github');
        $this->composerJsonStub($path);

        @mkdir($path . '/src');
        file_put_contents($path . "/src/{$generalName}ServiceProvider.php", $serviceProvider);

        $installCommand = $laravelClassGenerator->installCommand();
        @mkdir($path . '/src/Console');
        file_put_contents($path . "/src/Console/Install{$generalName}Command.php", $installCommand);

        $testCase = $laravelClassGenerator->testCase();
        file_put_contents($path . "/tests/TestCase.php", $testCase);
    }


    public function composerJsonStub($src)
    {
        $composerJsonContent = (new ComposerJsonGenerator(
            $this->packageName,
            $this->packageDescription,
            $this->authorName,
            $this->authorEmail,
            $this->namespace,
            $this->stub
        ))->generate();

        file_put_contents($src . '/composer.json', $composerJsonContent);
    }

    public function deleteContent($path)
    {
        try {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDot()) continue;
                if ($fileinfo->isDir()) {
                    if ($this->deleteContent($fileinfo->getPathname()))
                        @rmdir($fileinfo->getPathname());
                }
                if ($fileinfo->isFile()) {
                    @unlink($fileinfo->getPathname());
                }
            }
            rmdir($path);
        } catch (\Exception $e) {
            echo $e->getMessage();

            return false;
        }
        return true;
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
