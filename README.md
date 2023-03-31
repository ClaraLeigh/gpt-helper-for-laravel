# GPT Helper for Laravel

This package provides basic integration of GPT (ChatGPT) with Laravel, allowing you to generate templates and modify files using ChatGPT. With the powerful GPT models and Laravel's artisan commands, you can easily customize and enhance your Laravel application.

## Installation

You can install the package via composer:

```bash
composer require ClaraLeigh/gpt-helper-for-laravel
```
The package will automatically register itself.

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --provider="GptHelperForLaravel\GptServiceProvider" --tag="config"
```

This will create a gpt-helper.php config file in your config directory. You can set your ChatGPT API key, model, GPT settings, and Domain-Driven Design starting directory in the configuration file.

## Publish Language Files

You can publish the language files with:

```bash
php artisan vendor:publish --provider="GptHelperForLaravel\GptServiceProvider" --tag="lang"
```

This will create a resources/lang/vendor/gpt-helper directory, where you can store your language files.

## Usage

### Try to predict the contents of a file, with context and a prompt

```bash
php artisan gpt:predict
 {source : The file we wish to predict}
 {--prompt= : Add additonal text to give GPT context}
 {--files= : A comma separated list of classnames/files to use as context}
 {--summarizedFiles= : Same as files, but use a summary of instead to reduce the query}
```

### Auto generate files during creation

For example, in your custom command:

```bash
php artisan make:model Books --prompt="This is a books model, with authors, genre's, publication dates and a relevant library"
```

This will create a Books model in your app directory, and the contents of the file will be modified using ChatGPT.

## Available Templates

The following templates are available:

- Model
- Controller
- ... more coming soon

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
