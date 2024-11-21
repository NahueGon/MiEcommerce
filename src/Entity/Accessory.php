<?php

namespace App\Entity;

use App\Repository\AccessoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessoryRepository::class)]
#[ORM\Table(name: 'accessory')]
class Accessory extends Product
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
