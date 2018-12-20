<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Pintushi\Bundle\UserBundle\Entity\TimestampableInterface;
use Pintushi\Bundle\UserBundle\Entity\ToggleableInterface;
use Pintushi\Bundle\UserBundle\Entity\UserInterface;

interface OrganizationInterface extends
    ToggleableInterface,
    \Serializable,
    TimestampableInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Set id
     *
     * @param int $id
     * @return Organization
     */
    public function setId(int $id): Organization;

    /**
     * Set name
     *
     * @param string $name
     * @return Organization
     */
    public function setName(string $name): Organization;

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string;

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description);

    public function getDescription(): ?string;

    /**
     * Serializes organization
     *
     * @return string
     */
    public function serialize(): string;

    /**
     * Unserializes organization
     *
     * @param $serialized
     */
    public function unserialize($serialized): void;

    public function setUsers(ArrayCollection $users): void;

    /**
     * Add User to Organization
     *
     * @param UserInterface $user
     */
    public function addUser(UserInterface $user): void;

    /**
     * Delete User from Organization
     *
     * @param UserInterface $user
     */
    public function removeUser(UserInterface $user): void;

    /**
     * Check if organization has specified user assigned to it
     *
     * @param UserInterface $user
     * @return bool
     */
    public function hasUser(UserInterface $user): bool;

    public function getUsers(): ArrayCollection;
}
