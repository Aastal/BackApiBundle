<?php

namespace Geoks\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

class Image
{
    /**
     * @var mixed
     *
     * @Expose
     * @Groups({"details", "list"})
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @Expose
     * @Groups({"details"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @Expose
     * @Groups({"details"})
     */
    protected $updated;

    /**
     * @var string
     *
     * @Expose
     * @Groups({"details", "list"})
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $isResize = false;

    /**
     * Image constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function prePersist()
    {
        $now = new \DateTime('now');

        $this->created = $now;
        $this->updated = $now;
    }

    public function preUpdate()
    {
        $now = new \DateTime('now');

        $this->updated = $now;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isIsResize()
    {
        return $this->isResize;
    }

    /**
     * @param boolean $isResize
     */
    public function setIsResize($isResize)
    {
        $this->isResize = $isResize;
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
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}
