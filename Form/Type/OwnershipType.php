<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

class OwnershipType
{
    const OWNER_TYPE_NONE = 'NONE';
    const OWNER_TYPE_USER = 'USER';
    const OWNER_TYPE_BUSINESS_UNIT = 'BUSINESS_UNIT';
    const OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';


    public function getOwnershipsArray()
    {
        return  array(
            self::OWNER_TYPE_NONE => 'None',
            self::OWNER_TYPE_USER => 'User',
            self::OWNER_TYPE_BUSINESS_UNIT => 'Business Unit',
            self::OWNER_TYPE_ORGANIZATION => 'Organization',
        );
    }
}
