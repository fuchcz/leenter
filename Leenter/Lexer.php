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
class Lexer
{
    /** @var array patterns to find */
    private $patterns;

    /** @var array found tokens */
    private $tokens;

    /**
     * Register regex pattern to recognize token.
     * @param $pattern string pattern regex
     * @param $tokenId string id to recognize token
     */
    public function registerPattern($pattern, $tokenId)
    {
        $this->patterns[$tokenId] = $pattern;
    }

    /**
     * Return token from the top of the stack.
     * @return Token
     */
    public function nextToken()
    {
        return array_shift($this->tokens);
    }

    /**
     * Push extra token on the top of the stack.
     * @param $token Token token to push
     */
    public function pushToken($token)
    {
        array_unshift($this->tokens, $token);
    }

    /**
     * Split string into tokens.
     * @param $string
     */
    public function tokenize($string)
    {
        $this->tokens = array();
        $offset = 0;
        while (isset($string[$offset])) {
            foreach ($this->patterns as $tokenId => $pattern) {
                if (preg_match('/' . $pattern . '/Ai', $string, $matches, 0, $offset)) {
                    if ($tokenId == 'T_END_TAG') $matches[0] = strtolower($matches[0]);
                    $this->tokens[] = new Token($tokenId, $matches[0]);
                    $offset += strlen($matches[0]);
                    continue 2;
                }
            }
            // unexpected character
            $this->tokens[] = new Token('T_TEXT', $string[$offset]);
            $offset += 1;
        }
    }

}
