<?php

namespace Geoks\ApiBundle\Entity;

use Ivory\Serializer\Mapping\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("none")
 */
class Log
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var string
     *
     * @Assert\Choice(choices={"front", "back"})
     */
    private $type;

    /**
     * @var string
     *
     * @Assert\NotNull()
     */
    private $description;

    /**
     * @var array
     */
    private $context;

    public function prePersist()
    {
        $now = new \DateTime();

        $this->created = $now;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }
}
