<?php

namespace Pintushi\Bundle\OrganizationBundle\Factory;

use Videni\Bundle\RestBundle\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OwnershpiAwareResourceFactory implements FactoryInterface
{
    public function __construct(RequestStack $requestStack)
    {

    }

    public function createNew()
    {

    }
}
