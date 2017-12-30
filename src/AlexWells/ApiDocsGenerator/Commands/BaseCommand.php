<?php

namespace AlexWells\ApiDocsGenerator\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class BaseCommand extends Command
{
    /**
     * Overwrite previous line with new message.
     *
     * @param string $string
     * @param string $style
     * @param null|int|string $verbosity
     */
    protected function overwrite($string, $style = null, $verbosity = null)
    {
        if ($this->output->isDecorated()) {
            // Move the cursor to the beginning of the line
            $this->output->write("\x0D");

            // Erase the line
            $this->output->write("\x1B[2K");
        }

        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->write($styled, false, $this->parseVerbosity($verbosity));
    }

    /**
     * Add common styles.
     */
    protected function addStyles()
    {
        $this->output->getFormatter()->setStyle('warn', new OutputFormatterStyle('yellow'));
        $this->output->getFormatter()->setStyle('red', new OutputFormatterStyle('red'));
    }
}