<?php

namespace Vigneshc91\LaravelTestGenerator;

class Formatter
{
    protected array $cases;

    protected string $file;

    protected string $destinationFilePath;

    protected bool $sync;

    public function __construct(bool $sync)
    {
        $this->sync = $sync;
        $this->file = __DIR__ . '/Test/UserTest.php';
        $this->destinationFilePath = base_path('tests/Feature/');
        $this->cases = [];
    }


    public function format(array $case, string $url, string $method, string $controllerName, string $actionName, mixed $auth): void
    {
        $testName = $controllerName . $actionName;
        $this->cases[$testName]['action'] = $actionName;
        $this->cases[$testName]['url'] = $url;
        $this->cases[$testName]['method'] = $method;
        $this->cases[$testName]['params'] = $case;
        $this->cases[$testName]['auth'] = $auth;
        $this->cases[$testName]['directory'] = $controllerName;
        if (empty($this->cases[$testName]['function'])) {
            $this->cases[$testName]['function'] = [];
        }

        $this->formatFunction($testName);
    }

    /**
     * Generate the files for all the test cases
     */
    public function generate()
    {
        $this->createDirectory();
        $this->formatFile();
    }

    /**
     * Set the function for success and failure case
     */
    protected function formatFunction(string $testName): void
    {
        $functionName = '';
        $i = 0;
        $controller = $this->cases[$testName];

        foreach ($controller['params'] as $index => $item) {
            # Add function documentation
            $function = "\t" . '/**' . PHP_EOL . "\t" . ' * ' . $controller['action'] . PHP_EOL . "\t" . ' *' . PHP_EOL;

            # Check @depends to be added or not
            if ($this->sync) {
                if ($i > 0) {
                    $function .= "\t" . ' * @depends ' . $functionName . PHP_EOL;
                } else {
                    if (count($controller['function']) > 0) {
                        $function .= "\t" . ' * @depends ' . end($controller['function'])['name'] . PHP_EOL;
                    }
                }
            }

            $function .= "\t" . ' * @return void' . PHP_EOL . "\t" . ' */' . PHP_EOL;
            $functionName = $this->getFunctionName($index, $controller['action']);

            # Function name and declaration
            $function .= "\t" . 'public function ' . $functionName . '()';

            # Function definition
            $body = "\t\t" . '$response = $this->json(\'' . strtoupper($controller['method']) . '\', \'' . $controller['url'] . '\', [';

            # Request parameters
            $params = $this->getParams($item);
            $body .= $params ? PHP_EOL . $params . PHP_EOL . "\t\t" . ']' : ']';

            $body .= $controller['auth'] ? ", [\n\t\t\t'Authorization' => 'Bearer '\n\t\t]" : '';

            $body .= ');';
            # Assert response
            $body .= PHP_EOL . PHP_EOL . "\t\t" . '$response->assertStatus(' . ($index == 'failure' ? '400' : '200') . ');' . PHP_EOL;

            # Add the function to the global array
            $this->cases[$testName]['function'][] = [
                'name' => $functionName,
                'code' => $function . PHP_EOL . "\t" . '{' . PHP_EOL . $body . PHP_EOL . "\t" . '}' . PHP_EOL,
            ];

            $i++;
        }

    }

    /**
     * Format the test cases for the writing to the file
     */
    protected function formatFile(): void
    {
        foreach ($this->cases as $key => $value) {
            $lines = file($this->file, FILE_IGNORE_NEW_LINES);

            $dirName = $value['directory'];
            $namespace = "namespace Tests\Feature\\$dirName;";

            $lines[2] = $namespace;
            $lines[8] = $this->getClassName($key, $lines[8]);
            $functions = implode(PHP_EOL, array_column($value['function'], 'code'));
            $content = array_merge(array_slice($lines, 0, 10), [$functions], array_slice($lines, 11));

            $testDir = $this->destinationFilePath . DIRECTORY_SEPARATOR . $dirName;
            $this->createDirectory($testDir);
            $this->writeToFile($key . 'Test', $content, $testDir);
        }
    }


    protected function writeToFile(string $testName, array $content, string $dir): void
    {
        $fileName = $dir . '/' . $testName . '.php';
        $file = fopen($fileName, 'w');
        foreach ($content as $value) {
            fwrite($file, $value . PHP_EOL);
        }
        fclose($file);

        echo "\033[32m" . basename($fileName) . ' Created Successfully' . PHP_EOL;
    }


    protected function getClassName(string $testName, string $line): string
    {
        return str_replace('UserTest', $testName . 'Test', $line);
    }


    protected function getParams(array $param): string
    {
        if (empty($param)) {
            return '';
        }
        $param = json_encode($param);
        $param = str_replace(['{', '}'], '', $param);
        $param = "\t\t\t" . $param;
        $param = str_replace('":', '" => ', $param);
        $param = str_replace(',', ",\n\t\t\t", $param);
        return $param;
    }


    protected function getFunctionName(string $index, string $action): string
    {
        $name = 'test' . $action;
        return $index == 'failure' ? $name . 'WithError' : $name;
    }


    protected function createDirectory(?string $dir = null): void
    {
        $dirName = $dir ?? $this->destinationFilePath;
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }
    }
}