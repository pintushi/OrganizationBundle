<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Pintushi\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Pintushi\Bundle\FormBundle\Form\Type\SelectHiddenAutocompleteType;
use Pintushi\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Pintushi\Bundle\OrganizationBundle\Form\DataTransformer\BusinessUnitTreeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select business unit with autocomplete form type.
 */
class BusinessUnitSelectAutocomplete extends AbstractType
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var string */
    protected $entityClass;

    /**
     * BusinessUnitSelectAutocomplete constructor.
     *
     * @param EntityManager $entityManager
     * @param $entityClass
     * @param BusinessUnitManager $businessUnitManager
     */
    public function __construct(
        EntityManager $entityManager,
        $entityClass,
        BusinessUnitManager $businessUnitManager
    ) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['configs']['multiple']) &&  $options['configs']['multiple'] === true) {
            $builder->addModelTransformer(
                new EntitiesToIdsTransformer($this->entityManager, $this->entityClass)
            );
        } else {
            $builder->resetModelTransformers();
            $builder->addModelTransformer(
                new BusinessUnitTreeTransformer($this->businessUnitManager)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'multiple'    => true,
                    'component'   => 'tree-autocomplete',
                    'placeholder' => 'pintushi.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
                    'entity_id'   => null
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_type_business_unit_select_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SelectHiddenAutocompleteType::class;
    }
}
