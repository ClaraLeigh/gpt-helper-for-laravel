<?php

namespace GptHelperForLaravel\Traits;

trait HasRelatedFiles
{
    /**
     * Get related files to include in the prompt
     *
     * @return array|void
     */
    protected function getRelatedFiles()
    {
        $relatedFiles = [];
        if ( ! empty($this->option('summaryFiles'))) {
            $relatedFiles['summarized'] = $this->getFiles(true);
        }
        if ( ! empty($this->option('files'))) {
            $relatedFiles['files'] = $this->getFiles(false);
        }

        return $relatedFiles;
    }

    /**
     * Get the files to include in the prompt
     *
     * @return string
     */
    protected function relatedFilesPrompt(): string
    {
        $relatedFiles = $this->getRelatedFiles();

        $prompt = '';
        if ( ! empty($relatedFiles['summarized'])) {
            $prompt .= "When creating in file, please keep in mind these related summarized files. Assume for context but don't use this directly:".PHP_EOL."```".PHP_EOL;
            $prompt .= $relatedFiles['summarized'].PHP_EOL;
            $prompt .= "```".PHP_EOL;
        }
        if ( ! empty($relatedFiles['files'])) {
            $prompt .= "When creating in file, please keep in mind these related files:".PHP_EOL."```".PHP_EOL;
            $prompt .= $relatedFiles['files'].PHP_EOL;
            $prompt .= "```".PHP_EOL;
        }

        return $prompt;
    }
}