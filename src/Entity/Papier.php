<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\PapierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PapierRepository::class)
 */
class Papier
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("papier_read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("papier_read")
     */
    private $titre;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("papier_read")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("papier_read")
     */
    private $file;

    /**
     * @ORM\Column(type="boolean",options={"default": false})
     * @Groups("papier_read")
     */
    private $etat;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("papier_read")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("papier_read")
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="papiers")
     * @Groups("papier_read")
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("papier_read")
     */
    private $expert_id;

    /**
     * @ORM\OneToMany(targetEntity=Avis::class, mappedBy="papier", orphanRemoval=true)
     * @Groups("papier_read")
     */
    private $avis;


    public function __construct()
    {
        $this->avis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getExpertId(): ?int
    {
        return $this->expert_id;
    }

    public function setExpertId(?int $expert_id): self
    {
        $this->expert_id = $expert_id;

        return $this;
    }
    public function getAvis(): Collection
    {
        return $this->avis;
    }

}
