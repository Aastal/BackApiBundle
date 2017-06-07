<?php

namespace Geoks\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Geoks\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class Import
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var integer
     */
    protected $type;

    /**
     * @var File
     */
    protected $csv;

    /**
     * @ORM\ManyToOne(targetEntity="Geoks\AdminBundle\Entity\Image")
     */
    protected $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
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
     * @return File
     */
    public function getCsv()
    {
        return $this->csv;
    }

    /**
     * @param File $csv
     */
    public function setCsv($csv)
    {
        $this->csv = $csv;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function addImage(UploadedFile $image)
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    public function removeImage(UploadedFile $image)
    {
        $this->images->remove($image);

        return $this;
    }

    public function setImages($images)
    {
        $this->images = $images;
    }
}
