<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=AvisRepository::class)
 */
class Avis
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
    private $commentaire;

    /**
     * @ORM\Column(type="integer")
     * @Groups("papier_read")
     */
    private $score;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\ManyToOne(targetEntity=user::class)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Papier::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $papier;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): self
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

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

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPapier(): ?Papier
    {
        return $this->papier;
    }

    public function setPapier(?Papier $papier): self
    {
        $this->papier = $papier;

        return $this;
    }

}
