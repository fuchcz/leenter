<?php

/**
 * This file is part of Leenter (https://github.com/fuchcz/leenter)
 *
 * Copyright (c) 2012 Miroslav Pokorný (http://fuch.cz)
 */

namespace Leenter;

/**
 * Transform string into tokens.
 */
abstract class Parser
{

    /** @var string opening bracket char */
    protected $openingBracket = '[';

    /** @var string closing bracket char */
    protected $closingBracket = ']';

    /** @var string closing tag char */
    protected $closingTagChar = '/';

    /** @var Preprocessor preprocessor */
    protected $preprocessor;

    /** @var Lexer lexer */
    protected $lexer;

    /** @var CoreParser core parser */
    protected $parser;

    public function __construct($openingBracket = '[', $closingBracket = ']', $closingTagChar = '/')
    {
        $this->openingBracket = $openingBracket;
        $this->closingBracket = $closingBracket;
        $this->closingTagChar = $closingTagChar;
        // create preprocessor, lexer and coreparser
        $this->preprocessor = new Preprocessor();
        $this->lexer = new Lexer();
        $this->parser = new CoreParser($this->preprocessor, $this->lexer, $this);
        // add root tag
        $this->parser->registerTag(new Tag('T_DOCUMENT', Tag::BLOCK, 'document', array(Tag::BLOCK)));
    }

    /**
     * Parse text.
     * @param $string string text to convert
     */
    public function parse($string)
    {
        // parse string
        $this->parser->parse($string);
    }

    /**
     * Returns opening bracket char.
     * @return string
     */
    public function getOpeningBracket() {
        return $this->openingBracket;
    }

    /**
     * Return closing bracket char.
     * @return string
     */
    public function getClosingBracket() {
        return $this->closingBracket;
    }

    /**
     * Return closing tag char.
     * @return string
     */
    public function getClosingTagChar() {
        return $this->closingTagChar;
    }

}
