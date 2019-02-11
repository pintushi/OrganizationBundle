<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pintushi\Bundle\FormBundle\Form\Type\SelectHiddenAutocompleteType;

class OrganizationSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs' => [
                    'placeholder' => 'pintushi.organization.form.choose_organization',
                ],
                'autocomplete_alias' => 'user_organizations'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SelectHiddenAutocompleteType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_organization_select';
    }
}
