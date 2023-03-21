<?php

namespace GptHelperForLaravel;

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Symfony\Component\Console\Input\ArgvInput;

class FileModifier
{
    protected $gptApiService;

    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

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
     * @return mixed|string
     */
    public function modifyGeneratedFile($path, $contents)
    {
        $prompt = $this->getConsolePrompt();
        if (!$prompt) {
            return $contents;
        }

        return $this->getResponse($prompt, $path, $contents) ?? $contents;
    }

    public function getConsolePrompt()
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
            'action' => '/.*Action\.php$/',
            'controller' => '/.*Controller\.php$/',
            'model' => '/.*Model$/',
            'request' => '/.*Request\.php$/',
            'resource' => '/.*Resource\.php$/',
            'test' => '/.*Test\.php$/',
            'factory' => '/.*Factory\.php$/',
            'seeder' => '/.*Seeder\.php$/',
            'migration' => '/.*Migration\.php$/',
            'event' => '/.*Event\.php$/',
            'listener' => '/.*Listener\.php$/',
            'job' => '/.*Job\.php$/',
            'mail' => '/.*Mail\.php$/',
            'notification' => '/.*Notification\.php$/',
            'rule' => '/.*Rule\.php$/',
            'view' => '/.*View\.php$/',
            'blade' => '/.*Blade\.php$/',
            'markdown' => '/.*Markdown\.php$/',
            'component' => '/.*Component\.php$/',
            'middleware' => '/.*Middleware\.php$/',
            'provider' => '/.*Provider\.php$/',
            'channel' => '/.*Channel\.php$/',
            'exception' => '/.*Exception\.php$/',
            'console' => '/.*Console\.php$/',
            'command' => '/.*Command\.php$/',
            'trait' => '/.*Trait\.php$/',
            'interface' => '/.*Interface\.php$/',
            'enum' => '/.*Enum\.php$/',
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
     */
    protected function generateGptQuery($prompt, $fileType, $contents): string
    {
        $trans = app('translator');
        $trans->setLocale('en');
        $question = $trans->get('gpt-helper::prompts.start', ['file_type' => $fileType]);
        $question .= $trans->get('gpt-helper::prompts.content', ['content' => $contents]);
        $question .= $trans->get('gpt-helper::prompts.refinement');
        $question .= $trans->get('gpt-helper::prompts.types.' . $fileType) . ' ';
        $question .= $trans->get('gpt-helper::prompts.changes', ['changes' => $prompt]);
        $question .= $trans->get('gpt-helper::prompts.end');
        return $question;
    }

    /**
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
        $response = $this->gptApiService->ask($question);


        // get the string after ```php if it exists, otherwise return the after ```
        $response = strpos($response, '```php') !== false ?
            substr($response, strpos($response, '```php') + 7) :
            substr($response, strpos($response, '```') + 3);

        // get the string before ```
        $contents = substr($response, 0, strpos($response, '```'));

        // if we are running in the console, lets output the extra details, if its not empty
        if ($this->app->runningInConsole()) {
            $context = substr($response, strpos($response, '```') + 12);
            // strip any new lines and trim the string
            $test = trim(preg_replace('/\s+/', ' ', $context));
            if (!empty($test)) {
                echo $context;
            }
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
}