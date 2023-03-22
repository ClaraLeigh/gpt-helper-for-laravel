<?php

namespace GptHelperForLaravel;

use OpenAI\Laravel\Facades\OpenAI;

class GptApiService
{
    public $enabled = true;

    public function __construct()
    {
        try {
            OpenAI::chat();
        } catch (\Exception $e) {
            $this->enabled = false;
        }
    }

    public function ask($question): ?string
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [[
                    'role' => 'user',
                    'content' => $question,
                ]]
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            return null;
        }
    }
}
