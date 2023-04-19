<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\GptApiService;
use GptHelperForLaravel\Support\ClassNameResolver;
use GptHelperForLaravel\Support\Facades\SummarizeFileFacade;
use Illuminate\Support\Facades\File;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class Command extends \Illuminate\Console\Command
{
    protected ClassNameResolver $classResolver;

    public function __construct(
        protected GptApiService $gptApiService
    ) {
        $this->classResolver = new ClassNameResolver();
        parent::__construct();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getResponse($questions): int
    {
        // Tell the console we are asking the following question:
        $this->outputQuestion($questions);

        // Ask the GPI API for a response
        $response = $this->askGPT($questions);
        // Ask the use if they want to reply to the response, and loop until they say no
        while ($this->confirm('Would you like to reply to the response?')) {
            if ($this->confirm('Would you like to restart with the current output?')) {
                $questions = $this->generateGptQuery(
                    $this->ask('What would you like to say?'),
                    $response,
                    $questions
                );
            } else {
                $questions[] = [
                    'role'    => 'user',
                    'content' => $this->ask('What would you like to say?'),
                ];
            }
            $this->askGPT($questions);
        }

        return Command::SUCCESS;
    }

    /**
     * Output the current question to the console.
     *
     * @param $questions
     *
     * @return void
     */
    protected function outputQuestion($questions): void
    {
        $this->info('--- Query sent to ChatGPT ---');
        // loop through the $questions array, then for each $value, get the key and value of each item and echo it
        foreach ($questions as $question) {
            $this->info('------------------------');
            $role = $question['role'];
            $prompt = $question['content'];
            $this->info("{$role}: {$prompt}");
        }
        $this->info('--- End of Query ---');
    }

    /**
     * Ask the GPT API a question and return the response.
     *
     * @param  array  $questions
     *
     * @return string|void
     */
    protected function askGPT(array &$questions)
    {
        $response = $this->gptApiService->ask($questions);
        if (empty($response)) {
            $this->error('The GPT API returned an empty response.');
            exit(Command::FAILURE);
        }

        $this->info('--- Response from ChatGPT ---');
        $this->info($response);
        $this->info('--- End of Response ---');

        $questions[] = [
            'role'    => 'assistant',
            'content' => $response,
        ];

        return $response;
    }

    protected function generateGptQuery(?string $prompt, ?string $context = '', array $questions = [])
    {
        // filter out user and assistant questions
        $questions = array_filter($questions, function ($question) {
            return $question['role'] === 'user';
        });
        // Add the context to the questions
        if (!empty($context)) {
            $questions[] = [
                'role'    => 'assistant',
                'content' => $context,
            ];
        }
        // Add the prompt to the questions
        if (!empty($prompt)) {
            $questions[] = [
                'role'    => 'user',
                'content' => $prompt,
            ];
        }
    }

    protected function getFiles($summary): string
    {
        // Get all the related files, then get the contents of each file, then simplify the files
        $relatedFiles = $this->option($summary ? 'summaryFiles' : 'files');
        $relatedFiles = explode(',', $relatedFiles);
        $relatedFilesContents = [];
        foreach ($relatedFiles as $relatedFile) {
            // If this is a php class name, then convert it to a path, use resolve to get the path, then use reflection to get the path
            $path = $this->classResolver->resolve($relatedFile);
            $fileName = File::basename($path);
            if ($summary) {
                // Summarise the contents of the file
                $contents = SummarizeFileFacade::run($path);
            } else {
                // Get the contents of the file
                $contents = File::get($path);
            }
            $relatedFilesContents[$fileName] = $contents;
        }
        // Combine the contents of the related files into a single string
        $relatedFilesContents = implode(PHP_EOL, array_map(
            function ($content, $filename) {
                return $filename.PHP_EOL.$content.PHP_EOL.PHP_EOL;
            },
            $relatedFilesContents,
            array_keys($relatedFilesContents)
        ));
        // Trim the newlines from the end of the string
        $relatedFilesContents = rtrim($relatedFilesContents, PHP_EOL);

        return $relatedFilesContents;
    }
}