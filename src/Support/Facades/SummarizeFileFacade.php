<?php

namespace GptHelperForLaravel\Support\Facades;

use Illuminate\Support\Facades\Facade;

class SummarizeFileFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'summarize-file';
    }
}
