<?php

namespace App\Entity;

use App\Repository\ClothingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothingRepository::class)]
#[ORM\Table(name: 'clothing')]
class Clothing extends Product
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }
}
