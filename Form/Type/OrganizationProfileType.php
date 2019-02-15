<?php

declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\AbstractType;

final class OrganizationProfileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', Type\TextType::class, [
                    'required'    => true,
                    'label'       => 'pintushi.organization.name.label',
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add('description', Type\TextareaType::class, [
                'required' => false,
                'label'    => 'pintushi.organization.description.label'
            ])
        ;
    }

     /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_organization_profile';
    }
}
