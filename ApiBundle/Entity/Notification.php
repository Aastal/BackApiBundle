<?php

namespace Geoks\ApiBundle\Entity;

use Geoks\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use paragraph1\phpFCM\Notification as BaseNotification;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;

/**
 * @ORM\Table("notifications")
 * @ORM\Entity(repositoryClass="Geoks\ApiBundle\Entity\NotificationRepository")
 * @ExclusionPolicy("all")
 */
class Notification extends BaseNotification
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="body", type="string", length=255)
     */
    protected $body;

    /**
     * @var integer
     * @ORM\Column(name="type", type="integer")
     * @Assert\NotNull()
     */
    protected $type;

    /**
     * @var boolean
     * @ORM\Column(name="is_read", type="boolean")
     */
    protected $is_read = false;

    /**
     * @var User
     */
    protected $sender;

    /**
     * @var User
     */
    protected $receiver;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotNull()
     */
    protected $created_at;

    /**
     * Notification constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getTitle(), $this->getBody());

        $this->created_at = new \DateTime();
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return string
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return string
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param User $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return User
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @param User $receiver
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return boolean
     */
    public function isIsRead()
    {
        return $this->is_read;
    }

    /**
     * @param boolean $is_read
     */
    public function setIsRead($is_read)
    {
        $this->is_read = $is_read;
    }

    /**
     * @return integer
     */
    public function getNumVisitors()
    {
        if ($this->type == 4) {
            return $this->receiver->getViews();
        }

        return null;
    }
}
