<?php

namespace Geoks\AdminBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ChoiceList
{
    /**
     * @Required
     * @var array<string>
     */
    public $choices;
}