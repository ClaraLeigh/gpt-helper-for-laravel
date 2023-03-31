<?php

namespace GptHelperForLaravel\Commands;

use GptHelperForLaravel\GptApiService;
use GptHelperForLaravel\Support\ClassNameResolver;
use GptHelperForLaravel\Support\Facades\SummarizeFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PredictFileContents extends Command
{
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

    public function __construct(
        protected GptApiService $gptApiService
    )
    {
        $this->classResolver = new ClassNameResolver();
        parent::__construct();
    }

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

        // Get the contents of the file
        $sourceFileContents   = File::get($source);
        $relatedFiles = [];
        if (!empty($this->option('summaryFiles'))) {
            $relatedFiles['summarized'] = $this->getFiles(true);
        }
        if (!empty($this->option('files'))) {
            $relatedFiles['files'] = $this->getFiles(false);
        }

        // Compile a prompt
        $question = $this->generateGptQuery($this->option('prompt'), $sourceFileContents, $relatedFiles);

        return $this->getResponse($question);
    }

    protected function getResponse($question): int
    {
        // Tell the console we are asking the following question:
        $this->info('--- Query sent to ChatGPT ---');
        $this->info($question);
        $this->info('--- End of Query ---');

        // Ask the GPI API for a response
        $response = $this->gptApiService->ask($question);

        // Fall back to the original contents if the response is empty
        if (empty($response)) {
            $this->error('The GPT API returned an empty response.');
            return Command::FAILURE;
        }

        $this->info('--- Response from ChatGPT ---');
        $this->info($response);
        $this->info('--- End of Response ---');

        return Command::SUCCESS;
    }

    /**
     * Generate the query to send to the API
     *
     * @param string $prompt
     * @param string $source
     * @param array $relatedFiles
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function generateGptQuery($prompt, $source, $relatedFiles): string
    {
        $trans = app('translator');
        $trans->setLocale('en');
        // TODO: Why doesn't this work unless you publish the lang files?
        $question = $trans->get('gpt-helper::prompts.start') . PHP_EOL;
        $question .= $trans->get('gpt-helper::prompts.content', ['content' => $source]);
        $question .= PHP_EOL;
        if (!empty($relatedFiles['summarized'])) {
            $question .= "When creating in file, please keep in mind these related summarized files. Assume for context but don't use this directly:" . PHP_EOL . "```" . PHP_EOL;
            $question .= $relatedFiles['summarized'] . PHP_EOL;
            $question .= "```" . PHP_EOL;
        }
        if (!empty($relatedFiles['files'])) {
            $question .= "When creating in file, please keep in mind these related files:" . PHP_EOL . "```" . PHP_EOL;
            $question .= $relatedFiles['files'] . PHP_EOL;
            $question .= "```" . PHP_EOL;
        }
        $question .= $trans->get('gpt-helper::prompts.refinement');
        if (!empty($prompt)) {
            $question .= rtrim($prompt, ' ') . PHP_EOL;
        }
        $question .= $trans->get('gpt-helper::prompts.end');
        return $question;
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
                $contents = SummarizeFile::run($path);
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