<?php

namespace Pintushi\Bundle\OrganizationBundle\Provider;

use Pintushi\Bundle\ConfigBundle\Provider\AbstractProvider;

class OrganizationConfigurationFormProvider extends AbstractProvider
{
    const ORGANIZATION_TREE_NAME  = 'organization_configuration';

    /**
     * @var string
     */
    protected $parentCheckboxLabel = 'pintushi.organization.organization_configuration.use_default';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::ORGANIZATION_TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuTree()
    {
        return $this->getMenuTreeData(self::ORGANIZATION_TREE_NAME, self::CORRECT_MENU_NESTING_LEVEL);
    }

    /**
     * @param string $label
     */
    public function setParentCheckboxLabel($label)
    {
        $this->parentCheckboxLabel = $label;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel()
    {
        return $this->parentCheckboxLabel;
    }
}
