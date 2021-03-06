<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType;
use Pintushi\Bundle\OrganizationBundle\Provider\RequestBasedOrganizationProvider;

class OrganizationSelectorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(RequestBasedOrganizationProvider::QUERY_ID, OrganizationSelectType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'ownership_disabled' => true,
            ]
        );
    }
}
