<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Pintushi\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Pintushi\Bundle\UserBundle\Entity\TimestampableTrait;
use Pintushi\Bundle\UserBundle\Entity\ToggleableTrait;
use Pintushi\Bundle\UserBundle\Entity\UserInterface;
use Pintushi\Bundle\ShippingBundle\Entity\ShippingMethodInterface;
use Videni\Bundle\FileBundle\Annotation as FileAnnoation;
use Videni\Bundle\RestBundle\Model\ResourceInterface;

/*
 *@Config(
 *   defaultValues={
 *      "security"={
 *          "type"="ACL",
 *          "group_name"="",
 *          "category"="account_management"
 *   }
 * )
 * @FileAnnoation\File()
 */
class Organization implements OrganizationInterface, ResourceInterface
{
    use TimestampableTrait, ToggleableTrait;

    protected $id;

    protected $name;

    protected $global;

    protected $subdomain;

    protected $description;

    protected $expiredAt;

    protected $businessUnits;

    protected $users;

    /**
     * @FileAnnoation\Link()
     */
    protected $logo;

    public function __construct()
    {
        $this->businessUnits = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * @param \DateTime $expiredAt
     */
    public function setExpiredAt(?\DateTime $expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Organization
     */
    public function setId(int $id): Organization
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName(string $name): Organization
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function __toString(): string
    {
        return (string)$this->getName();
    }

    /**
     * Serializes organization
     *
     * @return string
     */
    public function serialize(): string
    {
        $result = serialize(
            array(
                $this->name,
                $this->enabled,
                $this->id
            )
        );
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        list(
            $this->name,
            $this->enabled,
            $this->id
            ) = unserialize($serialized);
    }

    public function setUsers(ArrayCollection $users): void
    {
        $this->users = $users;
    }

    /**
     * Add User to Organization
     *
     * @param UserInterface $user
     */
    public function addUser(UserInterface $user): void
    {
        if (!$this->hasUser($user)) {
            $this->getUsers()->add($user);
            $user->addOrganization($this);
        }
    }

    /**
     * Delete User from Organization
     *
     * @param UserInterface $user
     */
    public function removeUser(UserInterface $user): void
    {
        if ($this->hasUser($user)) {
            $this->getUsers()->removeElement($user);
            $user->removeOrganization($this);
        }
    }

    /**
     * Check if organization has specified user assigned to it
     *
     * @param UserInterface $user
     * @return bool
     */
    public function hasUser(UserInterface $user): bool
    {
        return $this->getUsers()->contains($user);
    }

    public function getUsers(): ArrayCollection
    {
        return $this->users;
    }

    /**
     * @param ArrayCollection $businessUnits
     *
     * @return $this
     */
    public function setBusinessUnits(ArrayCollection $businessUnits)
    {
        $this->businessUnits = $businessUnits;

        return $this;
    }

    public function getBusinessUnits(): ArrayCollection
    {
        return $this->businessUnits;
    }

    public function addBusinessUnit(BusinessUnit $businessUnit): self
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
        }

        return $this;
    }

    public function removeBusinessUnit(BusinessUnit $businessUnit): self
    {
        if ($this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->removeElement($businessUnit);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param mixed $logo
     *
     * @return self
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * @param mixed $subdomain
     *
     * @return self
     */
    public function setSubdomain($subdomain)
    {
        $this->subdomain = strtolower($subdomain);

        return $this;
    }

    /**
     * @return mixed
     */
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * @param mixed $global
     *
     * @return self
     */
    public function setGlobal($global)
    {
        $this->global = $global;

        return $this;
    }
}
