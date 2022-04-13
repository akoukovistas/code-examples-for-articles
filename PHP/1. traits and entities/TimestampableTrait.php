<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use DateTime;

/** @ORM\HasLifecycleCallbacks */
trait TimestampableTrait
{
    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private ?DateTime $createdAt = null;

    /**
     * @ORM\Column(type="datetime", name="updated_at", nullable=true)
     */
    private ?DateTime $updatedAt = null;

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt = null): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt = null): void
    {
        $this->updatedAt = $updatedAt;
    }

    /** @PrePersist */
    public function doStuffOnPrePersist(): void
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /** @PreUpdate */
    public function doStuffOnPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }
}
