# GPT Helper for Laravel

This package provides seamless integration of GPT (ChatGPT) with Laravel, allowing you to generate templates and modify files using ChatGPT. With the powerful GPT models and Laravel's artisan commands, you can easily customize and enhance your Laravel application.

## Installation

You can install the package via composer:

```bash
composer require ClaraLeigh/gpt-helper-for-laravel
```
The package will automatically register itself.

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --provider="GptHelperForLaravel\Console\GptTemplatesServiceProvider" --tag="config"
```

This will create a gpt-helper.php config file in your config directory. You can set your ChatGPT API key, model, GPT settings, and Domain-Driven Design starting directory in the configuration file.

## Publish Language Files

You can publish the language files with:

```bash
php artisan vendor:publish --provider="GptHelperForLaravel\Console\GptTemplatesServiceProvider" --tag="lang"
```

This will create a resources/lang/vendor/gpt-helper directory, where you can store your language files.

## Usage

Use the GPT Helper for Laravel in your existing artisan commands or create custom commands that leverage the ChatGPT API. Remember to pass the --prompt option to the commands to modify the file contents using ChatGPT.

For example, in your custom command:

```bash
php artisan make:model Books --prompt="This is a books model, with authors, genre's, publication dates and a relevant library"
```

This will create a Books model in your app directory, and the contents of the file will be modified using ChatGPT.

## Available Templates

The following templates are available:

- Model
- Controller
- Request
- Resource
- Migration
- Factory
- Seeder

## Available Settings

The following GPT settings are available:

- model
- max_tokens
- temperature
- n
- stop

## Testing

``` bash
composer test
```

## License

I am considering changing this license, open a issue if you have any suggestions.

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [ClaraLeigh](https://github.com/ClaraLeigh)
