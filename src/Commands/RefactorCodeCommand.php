<?php

namespace GptHelperForLaravel\Commands;

class RefactorCodeCommand extends Command
{
    protected $signature = 'gpt:refactor
                            {code : The PHP/Laravel code to be refactored.}
                            {--all : Apply all refactoring techniques.}
                            {--rename : Rename variables, methods, and classes for better readability.}
                            {--extract : Extract methods or functions to improve modularity.}
                            {--simplify : Simplify conditionals and loops.}
                            {--deduplicate : Remove code duplication.}
                            {--constants : Replace magic numbers with constants.}
                            {--helpers : Use Laravel\'s built-in helper functions and facades.}
                            {--patterns : Implement design patterns such as Repository, Factory, or Strategy.}
                            {--format : Improve code readability through formatting and comments.}';

    protected $description = 'Refactor PHP/Laravel code using GPT-3 based on the selected refactoring techniques.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $code = $this->argument('code');
        $questions = $this->getRefactoredCodeQuestions($code);
        $this->outputQuestion($questions);
        $refactoredCode = $this->askGPT($questions);
        $this->outputResponse($refactoredCode);

        $this->displayRefactoredCode($refactoredCode);
    }

    /**
     * Generate an array of options based on the command line options.
     *
     * @return array
     */
    protected function generateOptionsArray(): array
    {
        $options = [
            'all' => $this->option('all'),
            'rename' => $this->option('rename'),
            'extract' => $this->option('extract'),
            'simplify' => $this->option('simplify'),
            'deduplicate' => $this->option('deduplicate'),
            'constants' => $this->option('constants'),
            'helpers' => $this->option('helpers'),
            'patterns' => $this->option('patterns'),
            'format' => $this->option('format'),
        ];

        if (!array_filter($options)) {
            $options['all'] = true;
        }

        return $options;
    }

    /**
     * Generate the questions to be sent to GPT.
     *
     * @param  string|null  $prompt
     * @param  string|null  $context
     * @param $questions
     * @return array[]
     */
    protected function generateGptQuery(?string $prompt, ?string $context = '', $questions = []): array
    {
        $options = $this->generateOptionsArray();
        $refactoringTechniques = $this->generateRefactoringTechniquesList($options);

        if (empty($questions)) {
            $questions = [
                [
                    'role' => 'system',
                    'content' => "You are an AI code refactoring assistant. Refactor the provided PHP/Laravel code using the requested refactoring techniques."
                ],
                [
                    'role' => 'user',
                    'content' => "Apply these refactoring techniques: $refactoringTechniques."
                ],
            ];
        } else {
            // remove all user questions that are not system questions or the user prompt about refactoring techniques
            $questions = array_filter($questions, function ($question) {
                return $question['role'] === 'system' || str_contains($question['content'], 'Apply these refactoring techniques');
            });
        }

        $questions[] = [
            'role' => 'user',
            'content' => "Here's the code to refactor:\n$context"
        ];

        if ($prompt) {
            $questions[] = [
                'role' => 'user',
                'content' => $prompt
            ];
        }

        return $questions;
    }


    protected function generateRefactoringTechniquesList(array $options): string
    {
        $techniques = [];

        if ($options['all'] || $options['rename']) {
            $techniques[] = 'renaming variables, methods, and classes';
        }
        if ($options['all'] || $options['extract']) {
            $techniques[] = 'extracting methods or functions';
        }
        if ($options['all'] || $options['simplify']) {
            $techniques[] = 'simplifying conditionals and loops';
        }
        if ($options['all'] || $options['deduplicate']) {
            $techniques[] = 'removing code duplication';
        }
        if ($options['all'] || $options['constants']) {
            $techniques[] = 'replacing magic numbers with constants';
        }
        if ($options['all'] || $options['helpers']) {
            $techniques[] = 'using Laravel\'s built-in helper functions and facades';
        }
        if ($options['all'] || $options['patterns']) {
            $techniques[] = 'implementing design patterns such as Repository, Factory, or Strategy';
        }
        if ($options['all'] || $options['format']) {
            $techniques[] = 'improving code readability through formatting and comments';
        }

        return implode(', ', $techniques);
    }

    /**
     * Display the refactored code.
     *
     * @param  string  $refactoredCode
     * @return void
     */
    protected function displayRefactoredCode(string $refactoredCode): void
    {
        $this->info("Refactored code:\n$refactoredCode");
    }

}
