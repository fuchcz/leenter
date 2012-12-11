<?php

/**
 * This file is part of Leenter (https://github.com/fuchcz/leenter)
 *
 * Copyright (c) 2012 Miroslav Pokorný (http://fuch.cz)
 */

namespace Leenter;

/**
 * Preprocess string and prepare it for lexing.
 */
class Preprocessor
{
    /** @var array patterns to replace */
    private $patterns;

    /** @var array replacements */
    private $replacements;

    /**
     * Add rule to preprocessor.
     * @param $pattern string
     * @param $replacement string
     */
    public function registerPattern($pattern, $replacement)
    {
        $this->patterns[] = $pattern;
        $this->replacements[] = $replacement;
    }

    /**
     * Run preprocessing phase.
     * @param $text string to preprocess
     * @return string
     */
    public function preprocess($text)
    {
        $text = "[document]\n" . $text . "\n[/document]";
        $text = str_replace(array("\r\n", "\r"), array("\n", "\n"), $text);
        if (!empty($this->patterns)) {
            $text = preg_replace($this->patterns, $this->replacements, $text);
        }

        return $text;
    }

}
