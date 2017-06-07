<?php

namespace Geoks\AdminBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ImportField
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;
}