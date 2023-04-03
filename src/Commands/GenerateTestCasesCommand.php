<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\Support\Facades\SummarizeFileFacade;
use Illuminate\Support\Facades\File;

class GenerateTestCasesCommand extends Command
{
    protected $signature = 'gpt:generate-tests
                            {class : The class to generate test cases for.}
                            {--method= : (Optional) The specific method to generate test cases for.}
                            {--type= : (Optional) The type of tests to generate (unit, feature).}
                            ';

    protected $description = 'Generate test cases for a Laravel class using GPT.';

    public function handle()
    {
        $class = $this->argument('class');
        $method = $this->option('method');
        $type = $this->option('type') ?? 'feature';

        $gptPrompt = $this->generateGptPrompt($class, $method, $type);
        $questions = [
            [
                'role' => 'system',
                'content' => 'You are an advanced AI code generator that specializes in creating test code for Laravel, in PHP 8.1. Please generate the appropriate code and explanations for the requested test cases.'
            ],
            ['role' => 'user', 'content' => $gptPrompt]
        ];
        $this->getResponse($questions);

        // TODO: Put the content into the test file?
    }

    protected function generateGptPrompt(string $class, ?string $method, string $type): string
    {
        $prompt = "Generate {$type} test cases for the PHP Laravel class {$class}";
        if ($method) {
            $prompt .= " and specifically for the method {$method}";
        }
        $prompt .= ".";

        // Add file context
        $path = $this->classResolver->resolve($class);
        $fileName = File::basename($path);
        // Get the contents of the file
        $contents = File::get($path);

        $prompt .= "Context: $fileName contains the following code:\n$contents\n\n";

        $prompt .= "Provide code examples and brief explanations for each test case.";

        return $prompt;
    }

    private function createTestFile(string $class, string $type): string
    {
        $testNamespace = $type === 'unit' ? 'Unit' : 'Feature';
        $testClassName = $this->classResolver->getShortClassName($class).'Test';
        $testFilePath = base_path("tests/{$testNamespace}/{$testClassName}.php");

        if (!File::exists($testFilePath)) {
            $testFileContent = "<?php\n\nnamespace Tests\\{$testNamespace};\n\nuse {$class};\n\nclass {$testClassName} extends TestCase\n{\n    // Test cases will be inserted here\n}\n";
            File::put($testFilePath, $testFileContent);
        }

        return $testFilePath;
    }

    private function insertTestCases(string $testFilePath, string $testCasesCode)
    {
        $testFileContent = File::get($testFilePath);
        $testFileContent = str_replace('// Test cases will be inserted here', $testCasesCode, $testFileContent);
        File::put($testFilePath, $testFileContent);
    }
}
