<?php

namespace App\Entity\Traits;

use App\Entity\Images\Image;
use Doctrine\ORM\Mapping as ORM;

trait ImageableTrait
{
    /**
     * @ORM\OneToOne(targetEntity=Image::class, cascade={"persist", "remove"})
     */
    private $image;

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }
}