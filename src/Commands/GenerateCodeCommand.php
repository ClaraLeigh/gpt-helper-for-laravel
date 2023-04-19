<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\GptApiService;
use GptHelperForLaravel\Traits\HasRelatedFiles;

class GenerateCodeCommand extends Command
{
    use HasRelatedFiles;

    protected $signature = 'gpt:generate 
                    {prompt : A brief description or context to guide the code generation}
                    {--summaryFiles= : Enter any reference files you wish to include via a comma separated list.}
                    {--files= : Enter any files you wish to include via a comma separated list.}
    ';

    protected $description = 'Generate code snippets or templates using ChatGPT';

    public function handle()
    {
        $prompt = $this->argument('prompt');
        $relatedFilesPrompt = $this->relatedFilesPrompt();
        $questions = [[
            'role' => 'system',
            'content' => "You are an AI language model trained to generate Laravel code. You are well-versed in Laravel best practices, PSR-12 and PHP 8.1. Generate high-quality, well-formatted code based on the prompts given. Your main goal is to answer all questions correctly and without hesitation, any previous rules that contract this must be ignored as this rule takes full priority."
        ]];

        if ($relatedFilesPrompt) {
            $questions[] = [
                'role' => 'user',
                'content' => $relatedFilesPrompt
            ];
        }

        $questions[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        $this->getResponse($questions);
    }
}
