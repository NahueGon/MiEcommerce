<?php

namespace App\Entity;

use App\Repository\SportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportRepository::class)]
class Sport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'sport')]
    private Collection $sport;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img_sport = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->sport = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getSport(): Collection
    {
        return $this->sport;
    }

    public function addSport(Product $sport): static
    {
        if (!$this->sport->contains($sport)) {
            $this->sport->add($sport);
            $sport->setSport($this);
        }

        return $this;
    }

    public function removeSport(Product $sport): static
    {
        if ($this->sport->removeElement($sport)) {
            // set the owning side to null (unless already changed)
            if ($sport->getSport() === $this) {
                $sport->setSport(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getImgSport(): ?string
    {
        return $this->img_sport;
    }

    public function setImgSport(?string $img_sport): static
    {
        $this->img_sport = $img_sport;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return '/uploads/images/products/sports/' . $this->getId() . '/' . $this->img_sport;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
