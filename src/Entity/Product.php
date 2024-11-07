<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\File;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price_list = null;

    #[ORM\Column(nullable: true, options: ["default" => 0])]
    private ?int $views = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $img_product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true, options: ["default" => 0])]
    private ?int $stock = 0;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(nullable: true)]
    private ?float $price_sale = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\ManyToOne(inversedBy: 'sport')]
    private ?Sport $sport = null;

    #[ORM\ManyToOne(inversedBy: 'product')]
    private ?Brand $brand = null;

    public function __construct()
    {
        $this->created_at = new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $this->views = 0;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceList(): ?float
    {
        return $this->price_list;
    }

    public function setPriceList(float $price_list): static
    {
        $this->price_list = $price_list;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): static
    {
        $this->views = $views;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getImgProduct(): ?string
    {
        return $this->img_product;
    }

    public function setImgProduct(?string $img_product): static
    {
        $this->img_product = $img_product;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return '/uploads/images/products/categories/' . $this->getCategory()->getId() . '/' . $this->img_product;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getPriceSale(): ?float
    {
        return $this->price_sale;
    }

    public function setPriceSale(?float $price_sale): static
    {
        $this->price_sale = $price_sale;

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'Este campo es obligatorio'
        ]));
        $metadata->addPropertyConstraint('name', new Assert\Length([
            'min' => 3,
            'minMessage' => 'El nombre debe tener al menos 3 letras',
        ]));
        $metadata->addPropertyConstraint('name', new Regex([
            'pattern' => '/^[a-zA-Z0-9\s\'_-]+$/',
            'message' => 'El nombre solo debe contener letras',
        ]));

        $metadata->addPropertyConstraint('stock', new Assert\Length([
            'min' => 1,
            'minMessage' => 'La Cantidad debe tener al menos 1 numero',
        ]));
        $metadata->addPropertyConstraint('stock', new Regex([
            'pattern' => '/^\d+$/',
            'message' => 'La Cantidad  solo debe contener numeros',
        ]));

        $metadata->addPropertyConstraint('price_list', new Assert\NotBlank([
            'message' => 'Este campo es obligatorio'
        ]));
        $metadata->addPropertyConstraint('price_list', new Assert\Length([
            'min' => 1,
            'minMessage' => 'El Precio de lista debe tener al menos 1 numero',
        ]));
        $metadata->addPropertyConstraint('price_list', new Regex([
            'pattern' => '/^\d+(\.\d{1,2})?$/',
            'message' => 'El Precio de lista solo debe contener numeros',
        ]));

        $metadata->addPropertyConstraint('price_sale', new Assert\Length([
            'min' => 1,
            'minMessage' => 'El Precio final debe tener al menos 1 numero',
        ]));
        $metadata->addPropertyConstraint('price_sale', new Regex([
            'pattern' => '/^\d+(\.\d{1,2})?$/',
            'message' => 'El Precio de lista solo debe contener numeros',
        ]));

        $metadata->addPropertyConstraint('brand', new Assert\Length([
            'min' => 2,
            'minMessage' => 'La Marca debe tener al menos 2 caracteres',
        ]));

        $metadata->addPropertyConstraint('description', new Assert\Length([
            'min' => 15,
            'minMessage' => 'La Descripcion debe tener al menos 15 caracteres',
        ]));

        $metadata->addPropertyConstraint('img_product', new Assert\File([
            'maxSize' => '1024k',
            'maxSizeMessage' => 'Es demasiado pesada la imagen',
            'mimeTypes' => [
                    'image/jpg',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/jfif',
            ],
            'mimeTypesMessage' => 'Por favor sube un formato valido de imagen. "jpg, jpeg, png"',
        ]));
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getSport(): ?Sport
    {
        return $this->sport;
    }

    public function setSport(?Sport $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }
}
