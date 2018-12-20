<?php

namespace Pintushi\Bundle\OrganizationBundle\Entity;


interface BusinessUnitInterface
{
    public function getId(): int;

    public function getName(): string;
}
