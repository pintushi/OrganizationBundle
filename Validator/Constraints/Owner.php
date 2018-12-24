<?php

namespace Pintushi\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that the current logged in user
 * is granted to change the owner for an entity.
 * @Annotation
 */
class Owner extends Constraint
{
    public $message = 'The ownership {{ owner }} of entity {{ entityClass }} is not valid.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'owner_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
