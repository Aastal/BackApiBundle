<?php

namespace Geoks\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Geoks\AdminBundle\Annotation\ChoiceList;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Geoks\AdminBundle\Annotation\HasChoiceField;

/**
 * Class User
 * @package Geoks\UserBundle\Entity
 *
 * @ExclusionPolicy("all")
 * @HasChoiceField
 */
abstract class User extends BaseUser
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
    protected $firstname;

    /**
     * @var string
     *
     * @Expose
     * @Groups({"details", "list"})
     */
    protected $lastname;

    /**
     * @var string
     *
     * @Assert\Choice(choices = {"male", "female"}, message = "Choose a valid gender.")
     *
     * @Expose
     * @Groups({"details"})
     */
    protected $gender;

    /**
     * @var \DateTime
     */
    protected $dateOfBirth;

    /**
     * @var integer
     */
    protected $phone;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $gcmToken;

    /**
     * @Expose
     * @Groups({"details", "list"})
     *
     * @var string
     */
    protected $facebookUid;

    /**
     * @Expose
     * @Groups({"details"})
     *
     * @var string
     */
    protected $facebookName;

    /**
     * @var string
     */
    protected $facebookAccessToken;

    /**
     * @var array
     *
     * @ChoiceList(choices = {"ROLE_USER", "ROLE_ADMIN", "ROLE_SUPER_ADMIN"})
     */
    protected $roles;

    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        parent::__construct();

        $this->enabled = true;
    }

    public function __toString()
    {
        return parent::__toString();
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @VirtualProperty
     * @SerializedName("email")
     * @Groups({"details", "list"})
     *
     * @return string
     */
    public function getEmail()
    {
        return parent::getEmail();
    }

    public function getPassword()
    {
        return parent::getPassword();
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param \DateTime $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return string
     */
    public function getFacebookUid()
    {
        return $this->facebookUid;
    }

    /**
     * @param string $facebookUid
     */
    public function setFacebookUid($facebookUid)
    {
        $this->facebookUid = $facebookUid;
    }

    /**
     * @return string
     */
    public function getFacebookName()
    {
        return $this->facebookName;
    }

    /**
     * @param string $facebookName
     */
    public function setFacebookName($facebookName)
    {
        $this->facebookName = $facebookName;
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

    /**
     * @return mixed
     */
    public function getFacebookAccessToken()
    {
        return $this->facebookAccessToken;
    }

    /**
     * @param mixed $facebookAccessToken
     */
    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebookAccessToken = $facebookAccessToken;
    }

    /**
     * @return int
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param int $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getGcmToken()
    {
        return $this->gcmToken;
    }

    /**
     * @param mixed $gcmToken
     */
    public function setGcmToken($gcmToken)
    {
        $this->gcmToken = $gcmToken;
    }
}
