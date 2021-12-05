<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommandeRepository::class)
 */
class Commande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;
    
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Reservation", mappedBy="commande")
     * @ORM\JoinColumn(nullable=true)
     */
    private $reservations;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Restaurant", inversedBy="commandes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $restaurant;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Client", inversedBy="commandes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $client;

    public function __construct()
    {
        $this->creationDate = new \DateTime("now");
        $this->reservations = new ArrayCollection();
        $this->status = 'panier';
    }

    public function getTotalPrice(): float{
        //Cette méthode retourne le prix total de la Commande, basée sur la multiplication du prix des Plat réservés avec la quantity de la Reservation de la Commande
        $totalPrice = 0;
        foreach($this->reservations as $reservation){
            $totalPrice += ($reservation->getPlat()->getPrice() * $reservation->getQuantity());
        }
        return $totalPrice;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setCommande($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getCommande() === $this) {
                $reservation->setCommande(null);
            }
        }

        return $this;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): self
    {
        $this->restaurant = $restaurant;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }
}
