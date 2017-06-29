<?php

namespace Geoks\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Geoks\AdminBundle\Annotation\ChoiceList;
use Geoks\ApiBundle\EventListener;
use libphonenumber\PhoneNumber;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Geoks\AdminBundle\Annotation\HasChoiceField;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Geoks\ApiBundle\Annotation\FilePath;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Geoks\AdminBundle\Annotation\ImportField;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

/**
 * Class User
 * @package Geoks\UserBundle\Entity
 *
 * @ExclusionPolicy("all")
 * @HasChoiceField
 * @Vich\Uploadable
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
     * @Groups({"details", "list"})
     */
    protected $gender;

    /**
     * @var \DateTime
     */
    protected $dateOfBirth;

    /**
     * @var PhoneNumber
     *
     * @AssertPhoneNumber(type="MOBILE", defaultRegion="FR")
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


    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="user_image", fileNameProperty="imageName")
     */
    protected $imageFile;

    /**
     * @var string
     *
     * @Expose
     * @Groups({"details", "list"})
     * @FilePath(path="user_image", type="vich")
     */
    protected $imageName;

    /**
     * @var array
     *
     * @Expose
     * @Groups({"details", "list"})
     */
    protected $imageThumbs;

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
     * @return PhoneNumber
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

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return User
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            $this->updated = new \DateTime('now');
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @param string $imageName
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
    }

    /**
     * @return array
     */
    public function getImageThumbs()
    {
        return $this->imageThumbs;
    }

    /**
     * @param array $imageThumbs
     */
    public function setImageThumbs($imageThumbs)
    {
        $this->imageThumbs = $imageThumbs;
    }
}
