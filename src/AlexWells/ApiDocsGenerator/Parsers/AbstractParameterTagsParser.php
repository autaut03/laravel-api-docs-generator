<?php

namespace AlexWells\ApiDocsGenerator\Parsers;

use AlexWells\ApiDocsGenerator\Exceptions\InvalidTagFormat;
use Mpociot\Reflection\DocBlock\Tag;

abstract class AbstractParameterTagsParser
{
    /**
     * Parameter types.
     *
     * @var array
     */
    protected const PARAMETER_TYPES = ['query', 'path'];

    /**
     * Parameter descriptions.
     *
     * @var array
     */
    protected $descriptions;

    /**
     * Doc blocks to check.
     *
     * @var DocBlockWrapper[]
     */
    protected $docBlocks;

    /**
     * @param DocBlockWrapper[] $docBlocks
     */
    public function __construct(array $docBlocks)
    {
        $this->docBlocks = $docBlocks;
    }

    /**
     * Get tag name to find.
     *
     * @return string
     */
    abstract protected function getTagName();

    /**
     * Parse and transform parsed description any way you want here.
     *
     * @return mixed
     */
    protected function parseDescription($description)
    {
        return $description;
    }


    /**
     * Returns description.
     *
     * @param $in
     * @param $name
     *
     * @return mixed
     */
    public function get($in, $name)
    {
        return array_get($this->all(), "$in.$name");
    }

    /**
     * Returns all descriptions.
     *
     * @return array
     */
    public function all()
    {
        if(! $this->descriptions) {
            return $this->descriptions = $this->parseDescriptions();
        }

        return $this->descriptions;
    }

    /**
     * Parse tags from all doc blocks.
     *
     * @return array
     */
    protected function parseDescriptions()
    {
        $descriptions = [];

        foreach($this->docBlocks as $docBlock) {
            foreach ($docBlock->getDocTags($this->getTagName()) as $tag) {
                list($in, $name, $description) = $this->parseTag($tag);

                $descriptions[$in][$name] = $description;
            }
        }

        return $descriptions;
    }

    /**
     * Parse single tag.
     *
     * @param Tag $tag
     * @throws InvalidTagFormat
     *
     * @return array
     */
    protected function parseTag(Tag $tag)
    {
        $content = $tag->getContent();

        $parts = preg_split('/(\s+)/Su', $content, 3, PREG_SPLIT_DELIM_CAPTURE);

        if (! $parts || count($parts) !== 5) {
            throw new InvalidTagFormat(`Not enough arguments passed for {$tag->getName()}`);
        }

        $in = $parts[0];

        if (! in_array($in, static::PARAMETER_TYPES)) {
            throw new InvalidTagFormat(`Invalid parameter location specified for {$tag->getName()}`);
        }

        return [$in, $parts[2], $this->parseDescription($parts[4])];
    }
}