<?php

namespace Pintushi\Bundle\OrganizationBundle\Exception;

/**
 * @author Vidy Videni <videni@foxmail.com>
 */
class OrganizationNotFoundException extends \RuntimeException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(\Exception $previousException = null)
    {
        parent::__construct('Organization could not be found!', 0, $previousException);
    }
}
