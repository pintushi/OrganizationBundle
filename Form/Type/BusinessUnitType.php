<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Videni\Bundle\RestBundle\Form\Type\AbstractResourceType;

/**
 * Form for Business unit entity
 */
class BusinessUnitType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label'    => 'pintushi.organization.businessunit.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'parentBusinessUnit',
                BusinessUnitSelectAutocomplete::class,
                [
                    'required' => false,
                    'label' => 'pintushi.organization.businessunit.parent.label',
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'placeholder' => 'pintushi.business_unit.form.none_business_user',
                    'configs' => [
                        'multiple' => false,
                        'component'   => 'tree-autocomplete',
                        'width'       => '400px',
                        'placeholder' => 'pintushi.dashboard.form.choose_business_unit',
                        'allowClear'  => true
                    ]
                ]
            )
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * Change the autocomplete handler to "parent-business-units" for parentBusinessUnit field in case of
     * edit Business Unit page. The "parent-business-units" handler returns a list of Business units excluding
     * the given Business unit and its children.
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if (is_object($entity) && $entity->getId()) {
            $form->remove('parentBusinessUnit');
            $form->add(
                'parentBusinessUnit',
                BusinessUnitSelectAutocomplete::class,
                [
                    'required' => false,
                    'label' => 'pintushi.organization.businessunit.parent.label',
                    'autocomplete_alias' => 'parent-business-units',
                    'placeholder' => 'pintushi.business_unit.form.none_business_user',
                    'configs' => [
                        'multiple' => false,
                        'component'   => 'parent-business-units-autocomplete',
                        'width'       => '400px',
                        'placeholder' => 'pintushi.dashboard.form.choose_business_unit',
                        'allowClear'  => true,
                        'entity_id' => $entity->getId()
                    ]
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'ownership_disabled'      => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_business_unit';
    }
}
