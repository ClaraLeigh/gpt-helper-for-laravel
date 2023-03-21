<?php

namespace GptHelperForLaravel;

use Illuminate\Filesystem\Filesystem;

class GptFilesystem extends Filesystem
{
    public function put($path, $contents, $lock = false)
    {
        $contents = resolve('GptHelperForLaravel\\FileModifier')
            ->modifyGeneratedFile($path, $contents);

        return parent::put($path, $contents, $lock);
    }
}
