<?php

namespace Geoks\ApiBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class FilePath
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $type;

    /**
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $options)
    {
        if (isset($options['path'])) {
            $this->path = $options['path'];
        } else {
            throw new \InvalidArgumentException('The "path" attribute of FilePath is required.');
        }

        if (isset($options['type'])) {
            $this->type = $options['type'];
        }
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}