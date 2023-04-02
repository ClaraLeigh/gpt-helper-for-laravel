<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\GptApiService;
use GptHelperForLaravel\Support\ClassNameResolver;
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
        dump($questions);
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
}