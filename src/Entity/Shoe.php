<?php

namespace App\Entity;

use App\Repository\ShoeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoeRepository::class)]
#[ORM\Table(name: 'shoe')]
class Shoe extends Product
{
    #[ORM\Column(type: 'json', nullable: true)]
    private array $color = [];

    /**
     * @var Collection<int, SizeStock>
     */
    #[ORM\OneToMany(targetEntity: SizeStock::class, mappedBy: 'shoe', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['size' => 'ASC'])]
    private Collection $sizes;

    public function __construct()
    {
        parent::__construct();
        $this->sizes = new ArrayCollection();
    }

    public function getColor(): array
    {
        return $this->color;
    }

    public function setColor(array $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, SizeStock>
     */
    public function getSizes(): Collection
    {
        return $this->sizes;
    }

    public function addSize(SizeStock $size): static
    {
        if (!$this->sizes->contains($size)) {
            $this->sizes->add($size);
            if ($size->getShoe() !== $this) {
                $size->setShoe($this);
            }
        }

        return $this;
    }

    public function removeSize(SizeStock $size): static
    {
        if ($this->sizes->removeElement($size)) {
            // set the owning side to null (unless already changed)
            if ($size->getShoe() === $this) {
                $size->setShoe(null);
            }
        }

        return $this;
    }

    public function getTotalStock(): int
    {
        $totalStock = 0;

        foreach ($this->sizes as $sizeStock) {
            $totalStock += $sizeStock->getStock();
        }

        return $totalStock;
    }

    public function getSortedSizes(): Collection
    {
        $sizes = $this->sizes->toArray();
        usort($sizes, fn($a, $b) => $a->getSize() <=> $b->getSize());

        return new ArrayCollection($sizes);
    }
}
