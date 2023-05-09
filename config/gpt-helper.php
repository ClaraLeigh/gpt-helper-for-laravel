<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ChatGPT API Key
    |--------------------------------------------------------------------------
    |
    | Set your ChatGPT API key here. You can obtain the key from the OpenAI
    | Dashboard (https://platform.openai.com/signup/).
    |
    */

    'api_key' => env('GPT_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | ChatGPT Model
    |--------------------------------------------------------------------------
    |
    | Choose the desired ChatGPT model. By default, it is set to 'davinci-codex',
    | but you can use other available models, such as 'text-davinci-002' or
    | 'text-curie-002', based on your needs and use case.
    |
    */

    'model' => env('GPT_MODEL', 'gpt-3.5-turbo'),

    /*
    |--------------------------------------------------------------------------
    | GPT Settings
    |--------------------------------------------------------------------------
    |
    | Set the GPT settings like max_tokens, temperature, and other options.
    | These settings will be used while making requests to the ChatGPT API.
    |
    */

    'gpt_settings' => [
        'max_tokens' => env('GPT_MAX_TOKENS', 100),
        'temperature' => env('GPT_TEMPERATURE', 0.0),
        'n' => env('GPT_N', 1),
        'stop' => env('GPT_STOP', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain-Driven Design Starting Directory
    |--------------------------------------------------------------------------
    |
    | If you are using Domain-Driven Design, you can set your starting directory
    | here. This will be used as the base directory for generating templates.
    |
    */

    'ddd_directory' => env('DDD_DIR', ''),

];
