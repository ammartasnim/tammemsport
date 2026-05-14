<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isPromo = false;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $promoDiscount = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Categorie $categorie = null;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(targetEntity: LigneCommande::class, mappedBy: 'produit', cascade: ['remove'])]
    private Collection $ligneCommandes;

    public function __construct()
    {
        $this->ligneCommandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

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

    public function isPromo(): ?bool
    {
        return $this->isPromo;
    }

    public function setIsPromo(bool $isPromo): static
    {
        $this->isPromo = $isPromo;

        return $this;
    }

    public function getPromoDiscount(): ?int
    {
        return $this->promoDiscount;
    }

    public function setPromoDiscount(?int $promoDiscount): static
    {
        $this->promoDiscount = $promoDiscount;

        return $this;
    }

    public function getDiscountedPrice(): float
    {
        if ($this->isPromo && $this->promoDiscount !== null && $this->prix !== null) {
            return round($this->prix * (1 - $this->promoDiscount / 100), 3);
        }
        return $this->prix ?? 0;
    }

    #[Assert\Callback]
    public function validatePromoDiscount(ExecutionContextInterface $context): void
    {
        if ($this->isPromo && $this->promoDiscount === null) {
            $context->buildViolation('La remise est obligatoire pour une promotion.')
                ->atPath('promoDiscount')
                ->addViolation();
        }

        if ($this->isPromo && $this->promoDiscount !== null) {
            if ($this->promoDiscount < 1 || $this->promoDiscount > 100) {
                $context->buildViolation('La remise doit être entre 1 et 100%.')
                    ->atPath('promoDiscount')
                    ->addViolation();
            }
        }
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLigneCommandes(): Collection
    {
        return $this->ligneCommandes;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): static
    {
        if (!$this->ligneCommandes->contains($ligneCommande)) {
            $this->ligneCommandes->add($ligneCommande);
            $ligneCommande->setProduit($this);
        }

        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): static
    {
        if ($this->ligneCommandes->removeElement($ligneCommande)) {
            // set the owning side to null (unless already changed)
            if ($ligneCommande->getProduit() === $this) {
                $ligneCommande->setProduit(null);
            }
        }

        return $this;
    }

    
}
