<?php

namespace GptHelperForLaravel;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;

class FileModifier
{
    /**
     * @var GptApiService
     */
    protected GptApiService $gptApiService;

    /**
     * The application implementation.
     *
     * @var ApplicationContract
     */
    protected ApplicationContract $app;

    public function __construct(
        GptApiService $gptApiService,
        ApplicationContract $app
    )
    {
        $this->gptApiService = $gptApiService;
        $this->app = $app;
    }

    /**
     * Modify the generated file based on the file type and console prompt
     *
     * @param $path
     * @param $contents
     * @return string
     */
    public function modifyGeneratedFile($path, $contents): string
    {
        $prompt = $this->getConsolePrompt();
        if (!$prompt) {
            return $contents;
        }

        if (!$this->gptApiService->enabled && $this->app->runningInConsole()) {
            $this->consoleError('GPT Helper is not enabled. Please check your .env file.');
            return $contents;
        }

        return $this->getResponse($prompt, $path, $contents) ?? $contents;
    }

    /**
     * Get the prompt from the user
     *
     * @return mixed|null
     */
    public function getConsolePrompt(): ?string
    {
        if ($this->app->runningInConsole() &&
            ($input = new ArgvInput())->hasParameterOption('--prompt')) {
            return $input->getParameterOption('--prompt');
        }

        return null;
    }

    /**
     * Check the file type based on the file path
     *
     * @param $path
     * @return string
     */
    public function checkFileType($path): string
    {
        // Use regex or any other method to determine the file type
        // based on the file path and return the file type
        $types = [
            'action' => '/.*Action$/',
            'controller' => '/.*Controller$/',
            'model' => '/.*Model$/',
            'request' => '/.*Request$/',
            'resource' => '/.*Resource$/',
            'test' => '/.*Test$/',
            'factory' => '/.*Factory$/',
            'seeder' => '/.*Seeder$/',
            'migration' => '/.*Migration$/',
            'event' => '/.*Event$/',
            'listener' => '/.*Listener$/',
            'job' => '/.*Job$/',
            'mail' => '/.*Mail$/',
            'notification' => '/.*Notification$/',
            'rule' => '/.*Rule$/',
            'view' => '/.*View$/',
            'blade' => '/.*Blade$/',
            'markdown' => '/.*Markdown$/',
            'component' => '/.*Component$/',
            'middleware' => '/.*Middleware$/',
            'provider' => '/.*Provider$/',
            'channel' => '/.*Channel$/',
            'exception' => '/.*Exception$/',
            'console' => '/.*Console$/',
            'command' => '/.*Command$/',
            'trait' => '/.*Trait$/',
            'interface' => '/.*Interface$/',
            'enum' => '/.*Enum$/',
        ];
        foreach ($types as $type => $regex) {
            if (preg_match($regex, $path)) {
                return $type;
            }
        }
        return 'laravel';
    }

    /**
     * Generate the GPT query based on the file details
     *
     * @param $prompt
     * @param $fileType
     * @param $contents
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function generateGptQuery($prompt, $fileType, $contents): string
    {
        $trans = app('translator');
        $trans->setLocale('en');
        // TODO: Why doesn't this work unless you publish the lang files?
        $question = $trans->get('gpt-helper::prompts.start', ['file_type' => $fileType]);
        $question .= $trans->get('gpt-helper::prompts.content', ['content' => $contents]);
        $question .= $trans->get('gpt-helper::prompts.refinement');
        $question .= $trans->get('gpt-helper::prompts.types.' . $fileType) . ' ';
        $question .= $trans->get('gpt-helper::prompts.changes', ['prompt' => $prompt]);
        $question .= $trans->get('gpt-helper::prompts.end');
        return $question;
    }

    /**
     * Get the response from the GPT API
     *
     * @param mixed $prompt
     * @param $path
     * @param $contents
     * @return string|null
     */
    public function getResponse(mixed $prompt, $path, $contents): ?string
    {
        $question = $this->generateGptQuery(
            $prompt,
            $this->checkFileType($path),
            $contents
        );
        // Tell the console we are asking the following question:
        $this->consoleInfoMessage('--- Query sent to ChatGPT ---', $question);

        // Ask the GPI API for a response
        $response = $this->gptApiService->ask($question);

        // get the string after ```php if it exists, otherwise return the after ```
        $response = strpos($response, '```<?php') !== false ?
            substr($response, strpos($response, '```<?php') + 7) :
            substr($response, strpos($response, '```') + 3);

        // get the string before ```
        $contents = substr($response, 0, strpos($response, '```'));

        // Context for the command line if its there
        $context = substr($response, strpos($response, '```') + 12);
        // strip any new lines and trim the string
        $test = trim(preg_replace('/\s+/', ' ', $context));
        if (!empty($test)) {
            $this->consoleInfoMessage('--- Additional GPT Context ---', $context);
        }

        // Fall back to the original contents if the response is empty
        if (empty($contents)) {
            $contents = $response;
        }
        if (empty($contents)) {
            return null;
        }

        // check that it contains <?php, if not, add it
        if (strpos($contents, '<?php') === false) {
            $contents = '<?php' . PHP_EOL . $contents;
        }

        return $contents;
    }

    /**
     * Console error messages
     *
     * @return void
     */
    protected function consoleError(string $output): void
    {
        echo "\033[31m" . PHP_EOL . PHP_EOL;
        echo '-- GPT Helper Error --'. PHP_EOL;
        echo $output;
        echo PHP_EOL . PHP_EOL . "\033[0m";
    }


    /**
     * Console info messages
     *
     * @return void
     */
    protected function consoleInfoMessage(string $title, string $body = ''): void
    {
        echo "\033[32m" . PHP_EOL . PHP_EOL;
        echo $title . PHP_EOL;
        if (!empty($body)) {
            echo '-----------------------------' . PHP_EOL;
            echo "\033[0m";
            echo PHP_EOL . $body . PHP_EOL;
            echo "\033[32m";
            echo PHP_EOL . '-----------------------------' . PHP_EOL;
        }
        echo PHP_EOL . PHP_EOL . "\033[0m";
    }
}