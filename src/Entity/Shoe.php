<?php

namespace App\Entity;

use App\Repository\ShoeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShoeRepository::class)]
#[ORM\Table(name: 'shoe')]
class Shoe extends Product
{
    #[ORM\Column(nullable: true)]
    private ?string $size = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $color = [];

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
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
}
