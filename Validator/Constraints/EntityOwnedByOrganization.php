<?php

namespace Pintushi\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EntityOwnedByOrganization extends Constraint
{
    public $message = " {{target}} is not owned by organization {{organizationName}}";

    public $fields = array();

    public function getRequiredOptions()
    {
        return array('fields');
    }

    /**
     * @inheritdoc
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
