<?php

/**
 * This file is part of Leenter (https://github.com/fuchcz/leenter)
 *
 * Copyright (c) 2012 Miroslav Pokorný (http://fuch.cz)
 */

namespace Leenter;

/**
 * Stack token.
 */
class Token
{
    /** @var int token's name */
    private $tokenId;

    /** @var string token's content */
    private $content;

    public function __construct($tokenId, $content)
    {
        $this->tokenId = $tokenId;
        $this->content = $content;
    }

    /**
     * Returns token's id.
     * @return int
     */
    public function getId()
    {
        return $this->tokenId;
    }

    /**
     * Returns token's content.
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

}
