<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Pintushi\Bundle\UserBundle\Entity\User;
use Pintushi\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Videni\Bundle\RestBundle\Model\ResourceInterface;

/**
 * @Config(
 *  defaultValues={
 *    "ownership"={
 *           "owner_type"="BUSINESS_UNIT",
 *           "owner_field_name"="owner",
 *           "owner_column_name"="business_unit_owner_id",
 *           "organization_field_name"="organization",
 *           "organization_column_name"="organization_id"
 *       },
 *       "security"={
 *           "type"="ACL",
 *           "group_name"="",
 *           "category"="account_management"
 *       }
 *    }
 * )
 */
class BusinessUnit implements
    BusinessUnitInterface,
    ResourceInterface
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Organization
     */
    protected $organization;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    protected $users;

    /**
     * @var BusinessUnit
     */
    protected $owner;

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
     * Set name
     *
     * @param string $name
     *
     * @return BusinessUnit
     */
    public function setName(string $name): BusinessUnit
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     *
     * @return BusinessUnit
     */
    public function setOrganization(Organization $organization): BusinessUnit
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * Get user created date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get user last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->createdAt;
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __toString(): string
    {
        return (string)$this->getName();
    }

    public function getUsers(): ArrayCollection
    {
        $this->users = $this->users ?: new ArrayCollection();

        return $this->users;
    }

    public function setUsers(ArrayCollection $users): BusinessUnit
    {
        $this->users = $users;

        return $this;
    }

    public function addUser(User $user): BusinessUnit
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): BusinessUnit
    {
        if ($this->getUsers()->contains($user)) {
            $this->getUsers()->removeElement($user);
        }

        return $this;
    }

    public function getOwner(): ?BusinessUnit
    {
        return $this->owner;
    }

    public function setOwner(BusinessUnit $owningBusinessUnit): BusinessUnit
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    public function getParentBusinessUnit(): BusinessUnit
    {
        return $this->getOwner();
    }

    public function setParentBusinessUnit(BusinessUnit $value = null): BusinessUnit
    {
        $this->setOwner($value);

        return $this;
    }
}
