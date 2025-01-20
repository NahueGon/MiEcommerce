<?php

namespace App\Entity;

use App\Repository\SizeStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SizeStockRepository::class)]
class SizeStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "float")]
    private ?float $size = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\ManyToOne(targetEntity: Shoe::class, inversedBy: 'sizes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shoe $shoe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): ?float
    {
        return $this->size;
    }

    public function setSize(float $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getShoe(): ?Shoe
    {
        return $this->shoe;
    }

    public function setShoe(?Shoe $shoe): static
    {
        $this->shoe = $shoe;

        return $this;
    }

    public function __toString(): string
    {
        return (string) 'Hay ' . $this->getStock() . ' de Talle: ' . $this->getSize();
    }
}
