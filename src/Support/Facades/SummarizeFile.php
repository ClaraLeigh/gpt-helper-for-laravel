<?php

namespace GptHelperForLaravel\Support\Facades;

use Illuminate\Support\Facades\Facade;

class SummarizeFile extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'summarize-file';
    }
}
