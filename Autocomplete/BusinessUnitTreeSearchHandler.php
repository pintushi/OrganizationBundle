<?php

namespace Pintushi\Bundle\OrganizationBundle\Autocomplete;

use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class BusinessUnitTreeSearchHandler extends BusinessUnitOwnerSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }
}
