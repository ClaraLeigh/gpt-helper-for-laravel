<?php

namespace GptHelperForLaravel;

use OpenAI\Laravel\Facades\OpenAI;

class GptApiService
{
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
