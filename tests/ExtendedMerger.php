<?php

namespace EasyMerge2pdfTests;

use EasyMerge2pdf\Merger;

class ExtendedMerger extends Merger
{
    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCommandOptions(): array
    {
        return $this->buildCommand();
    }
}
