<?php

/**
 * This file is part of Leenter (https://github.com/fuchcz/leenter)
 *
 * Copyright (c) 2012 Miroslav Pokorný (http://fuch.cz)
 */

namespace Leenter;

/**
 * Core parser of Leenter.
 */
class CoreParser
{
    /** @var Preprocessor preprocessor */
    private $preprocessor;

    /** @var Lexer lexer */
    private $lexer;

    /** @var Parser parser */
    private $parser;

    /** @var Tag[] language tags */
    private $tags;

    /** @var Tag[] refs to tags by pattern key */
    private $tagsPatternRefs;

    /** @var Tag[] stack of mismatched tags */
    private $mismatchedTags = array();

    /** @var Tag current tag */
    private $currentTag;

    /** @var int current mode */
    private $currentMode = Tag::BLOCK;

    /** @var Token[] working stack */
    private $mainStack = array();

    /** @var Tag[] stack of opened tags */
    private $tagStack = array();

    public function __construct($preprocessor, $lexer, $parser)
    {
        $this->preprocessor = $preprocessor;
        $this->lexer = $lexer;
        $this->parser = $parser;
    }

    /**
     * Register recognizable tag.
     * @param $tag Tag tag to recognize
     * @throws \Exception
     */
    public function registerTag($tag)
    {
        if (is_array($this->tags) && array_key_exists($tag->getName(), $this->tags)) {
            throw new \Exception('Trying to add tag with already used name.');
        } else {
            $this->lexer->registerPattern('\\' . $this->parser->getOpeningBracket() . $tag->getPattern() . '\\' . $this->parser->getClosingBracket(), $tag->getName());
            $this->tags[$tag->getName()] = $tag;
            $this->tagsPatternRefs[$tag->getPattern()] = &$this->tags[$tag->getName()];
        }
    }

    /**
     * Return tag from closing string.
     * @param $closingTag string content of T_END_TAG token.
     * @return Tag
     * @throws \Exception
     */
    private function getTagFromClosing($closingTag)
    {
        $openingBracket = $this->parser->getOpeningBracket();
        $closingBracket = $this->parser->getClosingBracket();
        $closingTagChar = $this->parser->getClosingTagChar();
        if (
            (substr_compare($closingTag, $openingBracket, 0, strlen($openingBracket)) == 0) &&
            (substr_compare($closingTag, $closingTagChar, strlen($openingBracket), strlen($closingTagChar)) == 0) &&
            (substr_compare($closingTag, $closingBracket, -strlen($closingBracket), strlen($closingBracket)) == 0)
        ) {

            $tagPattern = substr($closingTag, strlen($openingBracket) + strlen($closingTagChar), -strlen($closingBracket));
            if (!array_key_exists($tagPattern, $this->tagsPatternRefs)) {
                throw new \Exception('Trying to close unknown tag.');
            } else {
                return $this->tagsPatternRefs[$tagPattern];
            }
        } else {
            throw new \Exception('Token is not T_END_TAG!');
        }
    }

    /**
     * Return parsed string.
     * @param $string string original string
     * @return string parsed string
     */
    public function parse($string)
    {
        // run startup methods
        $parserMethods = get_class_methods(get_class($this->parser));
        foreach ($parserMethods as $parserMethod) {
            if (substr($parserMethod, 0, 7) == 'startup' && is_callable(array($this->parser, $parserMethod))) {
                call_user_func(array($this->parser, $parserMethod), $string);
            }
        }
        // preprocess text
        $string = $this->preprocessor->preprocess($string);
        // tokenize
        $this->lexer->tokenize($string);

        // process tokens
        while (($token = $this->lexer->nextToken()) != NULL) {
            if (array_key_exists($token->getId(), $this->tags)) {
                $this->processOpeningTag($token);
            } elseif ($token->getId() == 'T_END_TAG') {
                $this->processClosingTag($token);
            } else {
                $this->processToken($token);
            }
        }

        return trim($this->mainStack[0]->getContent());
    }

    /**
     * Process token with opening / single tag.
     * @param $token Token current token
     */
    private function processOpeningTag($token)
    {
        $tag = $this->tags[$token->getId()];
        // check, whether is tag allowed in current mode, or current tag, if isn't close current tag.
        // todo: allow defining max depth
        if (!$tag->isAllowedIn($this->currentMode, $this->currentTag)) {
            $this->lexer->pushToken($token);
            $this->closeCurrentTag();

            return;
        }
        // if opening block tag in block mode, process preceding text
        if (($this->currentMode == Tag::BLOCK) && ($this->currentMode == $tag->getMode())) {
            $this->preparePragraph();
        }
        // if it's not single tag, change mode & current tag
        if ($tag->getMode() != Tag::SINGLE) {
            $this->currentMode = $tag->getMode();
            $this->currentTag = $tag;
            $this->tagStack[] = $tag;
            $this->mainStack[] = $token;
        } else {
            if (method_exists($this->parser, 'process' . $tag->getName())) {
                $this->finalizeToken(call_user_func(array($this->parser, 'process' . $tag->getName()), $token->getContent()));
            } else {
                $this->finalizeToken($token->getContent());
            }
        }
    }

    /**
     * Check whether closing tag is valid (not residing from mismatched tag or ending not opened tag)
     * @param $tag Tag current closing tag
     * @param $token Token current token
     * @return bool
     */
    private function checkClosingTagValidity($tag, $token)
    {
        if (is_array($this->mismatchedTags) && in_array($tag, $this->mismatchedTags)) {
            unset($this->mismatchedTags[array_search($tag, $this->mismatchedTags)]);

            return false;
        }

        // if it's ending tag for not currently opened tag
        if ($tag != $this->currentTag) {
            // if the tag is in stack, close current one
            if (in_array($tag, $this->tagStack)) {
                $this->lexer->pushToken($token);
                $this->closeCurrentTag();

                return false;
            } else {
                $this->mainStack[] = new Token('T_TEXT', $token->getContent());

                return false;
            }
        }

        return true;
    }

    /**
     * Process token with closing tag.
     * @param $token Token current token
     */
    private function processClosingTag($token)
    {
        try {
            $tag = $this->getTagFromClosing($token->getContent());
        } catch (\Exception $e) {
            $this->processToken(new Token('T_TEXT', $token->getContent()));

            return;
        }
        if (!$this->checkClosingTagValidity($tag, $token)) {return;}
        if ($this->currentMode == Tag::BLOCK) $this->preparePragraph();
        // get content
        $processedText = '';
        while (end($this->mainStack)->getId() != $tag->getName()) {
            $processingItem = array_pop($this->mainStack);
            $processedText = $processingItem->getContent() . $processedText;
        }
        array_pop($this->mainStack);
        // process content
        if (method_exists($this->parser, 'process' . $this->currentTag->getName())) {
            $processedText = call_user_func(array($this->parser, 'process' . $this->currentTag->getName()), $processedText);
        }
        // add to mainStack
        if ($this->currentTag->getMode() == Tag::INLINE) {
            $this->mainStack[] = new Token('T_TEXT', $processedText);
        } else {
            $this->finalizeToken($processedText);
        }
        array_pop($this->tagStack);
        if (count($this->tagStack) != 0) {
            $this->currentTag = end($this->tagStack);
            $this->currentMode = $this->currentTag->getMode();
        }

    }

    /**
     * Close current (mismatched) tag.
     */
    private function closeCurrentTag()
    {
        $mismatched = $this->currentTag;
        $this->processClosingTag(new Token('T_END_TAG', $this->parser->getOpeningBracket() . $this->parser->getClosingTagChar() . $this->currentTag->getPattern() . $this->parser->getClosingBracket()));
        $this->mismatchedTags[] = $mismatched;
    }


    /**
     * Finalize token.
     * @param $string
     */
    private function finalizeToken($string)
    {
        $trimmed = trim($string);
        if (!empty($trimmed)) {
            $this->mainStack[] = new Token('T_DONE', $string);
        }
    }

    /**
     * Prepare T_TEXT tokens in mainStack and finalize them.
     */
    private function preparePragraph()
    {
        $processedText = '';
        if (count($this->mainStack) == 0 || $this->currentTag->getName() != 'T_DOCUMENT') return;
        while ((end($this->mainStack)->getId() == 'T_TEXT' && end($this->mainStack)->getContent() != "\n") || end($this->mainStack)->getId() == 'T_SPACE') {
            $lastStackItem = array_pop($this->mainStack);
            $processedText = $lastStackItem->getContent() . $processedText;
        }
        $processedText = trim($processedText);
        if ($processedText != '') {
            if (method_exists($this->parser, 'processParagraph')) {
                $processedText = call_user_func(array($this->parser, 'processParagraph'), $processedText);
            }
            $this->finalizeToken($processedText);
        }
    }

    /**
     * Process rest of tokens.
     * @param $token Token current token
     */
    private function processToken($token)
    {
        if ($token->getId() == 'T_EOL') {
            $this->preparePragraph();
            $this->mainStack[] = new Token('T_TEXT', "\n");

            return;
        }
        $content = $token->getContent();
        if (method_exists($this->parser, 'process' . $token->getId())) {
            $content = call_user_func(array($this->parser, 'process' . $token->getId()), $content);
        }
        $this->mainStack[] = new Token($token->getId(), $content);
    }

}
