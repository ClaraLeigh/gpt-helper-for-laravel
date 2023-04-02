<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\GptApiService;

class GenerateCodeCommand extends Command
{
    protected $signature = 'gpt:generate {prompt : A brief description or context to guide the code generation}';

    protected $description = 'Generate code snippets or templates using ChatGPT';

    public function handle()
    {
        $prompt = $this->argument('prompt');
        $questions = [
            [
                'role' => 'system',
                'content' => "You are an advanced AI code generator that specializes in creating code in the Laravel community. Generate concise, well-formatted and functional code based on the user's prompts. Do not explain the code, only output the final contents of the generated code from the user request."
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $this->getResponse($questions);
    }
}
