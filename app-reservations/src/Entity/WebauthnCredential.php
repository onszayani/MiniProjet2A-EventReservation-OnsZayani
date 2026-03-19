<?php

namespace App\Entity;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;


#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
class WebauthnCredential
{
    #[ORM\Id] #[ORM\Column(type:"uuid")]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy:"webauthnCredentials")]
    #[ORM\JoinColumn(nullable:false, onDelete:"CASCADE")]
    private User $user;

    #[ORM\Column(type:"text")]
    private string $credentialData;  // JSON de PublicKeyCredentialSource

    #[ORM\Column(length:255)]
    private string $name;            // Ex: 'iPhone 15 de Ali'

    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column] private \DateTimeImmutable $lastUsedAt;

    public function __construct(User $user, string $name = 'Ma passkey')
    {
        $this->id=$user->getId() ? Uuid::v4() : Uuid::v4();  // toujours nouvel UUID
        $this->user=$user; $this->name=$name;
        $this->createdAt=$this->lastUsedAt=new \DateTimeImmutable();
    }

    public function getCredentialSource(): PublicKeyCredentialSource
    { return PublicKeyCredentialSource::createFromArray(json_decode($this->credentialData,true)); }

    public function setCredentialSource(PublicKeyCredentialSource $src): void
    { $this->credentialData = json_encode($src); }

    public function touch(): void { $this->lastUsedAt = new \DateTimeImmutable(); }
    public function getId():   Uuid   { return $this->id; }
    public function getUser(): User   { return $this->user; }
    public function getName(): string { return $this->name; }

}
