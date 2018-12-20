<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity\Ownership;

use Pintushi\Bundle\OrganizationBundle\Entity\OrganizationInterface;

trait OrganizationAwareTrait
{
    /**
     * @var OrganizationInterface
     *
     */
    protected $organization;

    /**
     * @return OrganizationInterface|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface|null $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }
}
