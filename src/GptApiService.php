<?php

namespace GptHelperForLaravel;

use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;

class GptApiService
{
    public $enabled = true;

    public function __construct()
    {
        try {
//            OpenAI::chat();
        } catch (\Exception $e) {
            $this->enabled = false;
        }
    }

    /**
     * Chat with the GPT API
     *
     * @param  string|array  $questions
     * @return string|null
     */
    public function ask(string|array $questions): ?string
    {
        if (is_string($questions)) {
            $questions = [[
                'role' => 'user',
                'content' => $questions,
            ]];
        }
        try {
            $client = $this->client();
            $response = $client->chat()->create([
                'model' => config('gpt-helper.model'),
                'temperature' => config('gpt-helper.gpt_settings.temperature'),
                'messages' => $questions
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            dd ($e->getMessage());
            return null;
        }
    }

    protected function client()
    {
        $apiKey = config('openai.api_key');
        $organization = config('openai.organization');
        return \OpenAI::factory()
            ->withApiKey($apiKey)
            ->withOrganization($organization) // default: null
            ->withHttpClient($client = new Client([
                'timeout'  => 150.0, // 2.5 minutes
            ]))
            ->make();
    }
}
