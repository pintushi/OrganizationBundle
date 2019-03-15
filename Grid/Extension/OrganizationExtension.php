<?php

namespace Pintushi\Bundle\OrganizationBundle\Grid\Extension;

use Pintushi\Bundle\GridBundle\Grid\Common\GridConfiguration;
use Pintushi\Bundle\GridBundle\Grid\Common\MetadataObject;
use Pintushi\Bundle\GridBundle\Extension\AbstractExtension;
use Pintushi\Bundle\GridBundle\Extension\Columns\ColumnInterface;
use Pintushi\Bundle\GridBundle\Provider\ConfigurationProvider;
use Pintushi\Bundle\GridBundle\Provider\State\GridStateProviderInterface;
use Pintushi\Bundle\FilterBundle\Filter\FilterInterface;
use Pintushi\Bundle\FilterBundle\Filter\FilterUtility;
use Pintushi\Component\PhpUtils\ArrayUtil;
use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Add organization column and filter to grid
 */
class OrganizationExtension extends AbstractExtension
{
    const DETECT_ORGNIZATION = '[options][detect_organization]';

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    private $doctrineHelper;

    private $ownershipMetadata = null;

    private $tokenAccessor = null;

    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        DoctrineHelper $doctrineHelper,
        TokenAccessorInterface $tokenAccessor
    ) {
         $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenAccessor = $tokenAccessor;
    }
    /**
     * {@inheritDoc}
     */
    public function isApplicable(GridConfiguration $config)
    {
        $organization = $this->tokenAccessor->getOrganization();

        return parent::isApplicable($config) &&
            $config->offsetGetByPath(self::DETECT_ORGNIZATION, false) &&
            $organization && $organization->isGlobal()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(GridConfiguration $config, MetadataObject $metadata)
    {
        $queryBuilder = $config->offsetGetByPath('[source][query_builder]');
        if (null === $queryBuilder) {
            return;
        }

        $ownershipMetadata = $this->getOwnershipMetadata($config->getExtendedEntityClassName());
        if (!$ownershipMetadata->hasOwner()) {
            return;
        }

        $alias = $queryBuilder->getRootAlias();

        $organizationFilter = [
            'type' => 'choice-tree',
            'path' => sprintf('%s.%s', $alias, $ownershipMetadata->getOrganizationColumnName()),
            'options' => [
                'autocomplete_alias' => 'user_organizations',
                'operator_options' => [
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                ]
            ],
            'label' => 'pintushi.user.organization.label',
        ];

        $metadata->offsetAddToArray('filters', [$organizationFilter]);

        $organizationColumn = [
            'path' => $ownershipMetadata->getOrganizationFieldName(),
            'name' => 'organization',
            'label' => 'pintushi.user.organization.label',
            'renderable' => true,
            'type' => 'string',
            'translatable' => true,
        ];

        $metadata->offsetAddToArray('columns', [$organizationColumn]);
    }

     /**
     * Get metadata for entity
     *
     * @param object|string $entitClass
     *
     * @return bool|OwnershipMetadataInterface
     * @throws \LogicException
     */
    private function getOwnershipMetadata($entitClass)
    {
        if ($this->ownershipMetadata) {
            return $this->ownershipMetadata;
        }

        if (!$this->doctrineHelper->isManageableEntity($entitClass)) {
            return null;
        }

        $this->ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entitClass);

        return $this->ownershipMetadata;
    }
}
