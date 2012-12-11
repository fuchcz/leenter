<?php

/**
 * This file is part of Leenter (https://github.com/fuchcz/leenter)
 *
 * Copyright (c) 2012 Miroslav Pokorný (http://fuch.cz)
 */

namespace Leenter;

/**
 * Language tag.
 */
class Tag
{
    /** single tag */
    const SINGLE = 0;

    /** block element */
    const BLOCK = 1;

    /** inline element */
    const INLINE = 2;

    /** @var string tag's name */
    private $name;

    /** @var int tag's mode */
    private $mode;

    /** @var string tag's pattern */
    private $pattern;

    /** @var array tags and/or modes that tag is allowed in */
    private $allowedIn;

    public function __construct($name, $mode, $pattern, $allowedIn)
    {
        $this->name = $name;
        $this->mode = $mode;
        $this->pattern = $pattern;
        $this->allowedIn = $allowedIn;
    }

    /**
     * Returns tag's name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns tag's mode.
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Returns tag's pattern.
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Returns whether is tag allowed in mode or tag.
     * @param $mode int mode
     * @param $tag Tag tag
     * @return bool
     */
    public function isAllowedIn($mode, $tag)
    {
        if (in_array($mode, $this->allowedIn) || in_array($tag->getName(), $this->allowedIn)) {
            return true;
        } else {
            return false;
        }
    }

}
