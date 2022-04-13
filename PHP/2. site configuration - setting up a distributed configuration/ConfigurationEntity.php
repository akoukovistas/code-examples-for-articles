<?php

namespace App\Entity;

use App\Entity\Common\Domain;
use App\Repository\ConfigurationRepository;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConfigurationRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Configuration
{
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $headConfig;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $navConfig;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $pageConfig;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $footerConfig;

    /**
     * @ORM\OneToOne(targetEntity=Domain::class, inversedBy="siteConfig", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $domain;

    public function getHead(): ?string
    {
        return $this->headConfig;
    }

    public function setHead(?string $headConfig): void
    {
        $this->headConfig = $headConfig;
    }

    public function getFooter(): ?string
    {
        return $this->footerConfig;
    }

    public function setFooter(?string $footerConfig): void
    {
        $this->footerConfig = $footerConfig;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNav(): ?string
    {
        return $this->navConfig;
    }

    public function setNav(?string $navConfig): void
    {
        $this->navConfig = $navConfig;
    }

    public function getOptions(): ?string
    {
        return $this->pageConfig;
    }

    public function setOptions(?string $pageConfig): void
    {
        $this->pageConfig = $pageConfig;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }
}
