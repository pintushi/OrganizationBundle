<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity\Ownership;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

trait BusinessUnitAwareTrait
{
    use OrganizationAwareTrait;

    /**
     * @var BusinessUnit
     */
    protected $owner;

    /**
     * @return BusinessUnit|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit|null $owner
     * @return $this
     */
    public function setOwner(BusinessUnit $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
