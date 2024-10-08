<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(groups: ['registration'])]
    private ?string $password = null;
    
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img_profile = null;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->created_at = new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->name . ' ' . $this->lastname;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getImgProfile(): ?string
    {
        return $this->img_profile;
    }

    public function setImgProfile(?string $img_profile): static
    {
        $this->img_profile = $img_profile;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        if (!$this->img_profile) {
            if($this->gender = 'male'){
                return '/uploads/images/profiles/defaultMaleImageProfile.jpg';
            }else if($this->gender = 'female'){
                return '/uploads/images/profiles/defaultFemaleImageProfile.jpg';
            }else{
                return '/uploads/images/profiles/defaultImageProfile.jpg';
            }
        }
        
        return '/uploads/images/profiles/' . $this->getId() . '/' . $this->img_profile;
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
            'pattern' => '/^[a-zA-Z]+$/',
            'message' => 'El nombre solo debe contener letras',
        ]));

        $metadata->addPropertyConstraint('lastname', new Assert\Length([
            'min' => 3,
            'minMessage' => 'El nombre debe tener al menos 3 letras',
        ]));
        $metadata->addPropertyConstraint('lastname', new Regex([
            'pattern' => '/^[a-zA-Z]+$/',
            'message' => 'El apellido solo debe contener letras',
        ]));

        $metadata->addPropertyConstraint('email', new Assert\NotBlank([
            'message' => 'Este campo es obligatorio'
        ]));
        $metadata->addPropertyConstraint('email', new Assert\Email([
            'message' => 'Ingresa un correo valido'
        ]));
        $metadata->addConstraint(new UniqueEntity([
            'fields' => 'email',
            'message' => 'Ya existe un usuario registrado con este email'
        ]));

        $metadata->addPropertyConstraint('password', new Assert\NotBlank([
            'message' => 'Este campo es obligatorio'
        ]));
        $metadata->addPropertyConstraint('password', new Assert\Length([
            'min' => 4,
            'minMessage' => 'La contraseña debe tener al menos 4 digitos',
        ]));
    }

}
