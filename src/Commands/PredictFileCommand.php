<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\Support\ClassNameResolver;
use GptHelperForLaravel\Support\Facades\SummarizeFileFacade;
use GptHelperForLaravel\Traits\HasRelatedFiles;
use Illuminate\Support\Facades\File;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PredictFileCommand extends Command
{
    use HasRelatedFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gpt:predict
                            {source : The path to the file you wish to predict.}
                            {--summaryFiles= : Enter any reference files you wish to include via a comma separated list.}
                            {--files= : Enter any files you wish to include via a comma separated list.}
                            {--prompt= : Enter in any details you wish to provide about the file.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Predict the contents of a file using your connected LLM.';

    protected ClassNameResolver $classResolver;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $source = $this->classResolver->resolve(
            $this->argument('source')
        );

        // Compile a prompt
        $question = $this->createInitalQuery(
            $this->option('prompt'),
            File::get($source)
        );

        $questions = [[
            'role' => 'user',
            'content' => $question,
        ]];

        return $this->getResponse($questions);
    }

    protected function createInitalQuery(?string $prompt, string $source): string
    {
        $trans = app('translator');
        $trans->setLocale('en');
        // TODO: Why doesn't this work unless you publish the lang files?
        $question = $trans->get('gpt-helper::prompts.start') . PHP_EOL;
        $question .= $trans->get('gpt-helper::prompts.content', ['content' => $source]);
        $question .= PHP_EOL;
        $question .= $this->relatedFilesPrompt();
        $question .= $trans->get('gpt-helper::prompts.refinement');
        if (!empty($prompt)) {
            $question .= rtrim($prompt, ' ') . PHP_EOL;
        }
        $question .= $trans->get('gpt-helper::prompts.end');
        return $question;
    }

    /**
     * Generate the query to send to the API
     *
     * @param string $prompt
     * @param string $context
     * @param array $relatedFiles
     *
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function generateGptQuery(?string $prompt, ?string $context = '', array $questions = []): array
    {
        $trans = app('translator');
        $trans->setLocale('en');
        // TODO: Why doesn't this work unless you publish the lang files?
        $question = $trans->get('gpt-helper::prompts.start') . PHP_EOL;
        $question .= $trans->get('gpt-helper::prompts.content', ['content' => $context]);
        $question .= PHP_EOL;
        $question .= $trans->get('gpt-helper::prompts.refinement');
        if (!empty($prompt)) {
            $question .= rtrim($prompt, ' ') . PHP_EOL;
        }
        $question .= $trans->get('gpt-helper::prompts.end');
        // TODO: Change this to using system prompts and another user prompt to clean up the code
        return [[
            'role' => 'user',
            'content' => $question,
        ]];
    }


}