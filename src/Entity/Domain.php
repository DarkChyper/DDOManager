<?php

namespace App\Entity;

use App\Repository\DomainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainRepository::class)]
class Domain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\OneToMany(mappedBy: 'domain', targetEntity: SubDomain::class, orphanRemoval: true)]
    private $subDomains;

    #[ORM\OneToMany(mappedBy: 'domain', targetEntity: DnsZone::class)]
    private $dnsZones;

    public function __construct()
    {
        $this->subDomains = new ArrayCollection();
        $this->dnsZones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, SubDomain>
     */
    public function getSubDomains(): Collection
    {
        return $this->subDomains;
    }

    public function addSubDomain(SubDomain $subDomain): self
    {
        if (!$this->subDomains->contains($subDomain)) {
            $this->subDomains[] = $subDomain;
            $subDomain->setDomain($this);
        }

        return $this;
    }

    public function removeSubDomain(SubDomain $subDomain): self
    {
        if ($this->subDomains->removeElement($subDomain)) {
            // set the owning side to null (unless already changed)
            if ($subDomain->getDomain() === $this) {
                $subDomain->setDomain(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DnsZone>
     */
    public function getDnsZones(): Collection
    {
        return $this->dnsZones;
    }

    public function addDnsZone(DnsZone $dnsZone): self
    {
        if (!$this->dnsZones->contains($dnsZone)) {
            $this->dnsZones[] = $dnsZone;
            $dnsZone->setDomain($this);
        }

        return $this;
    }

    public function removeDnsZone(DnsZone $dnsZone): self
    {
        if ($this->dnsZones->removeElement($dnsZone)) {
            // set the owning side to null (unless already changed)
            if ($dnsZone->getDomain() === $this) {
                $dnsZone->setDomain(null);
            }
        }

        return $this;
    }
}
