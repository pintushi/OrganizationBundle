<?php

declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Videni\Bundle\RestBundle\Form\Type\AbstractResourceType;

final class OrganizationType extends AbstractResourceType
{
    private $organizationRepository;

    public function __construct(
        string $dataClass,
        array $validationGroups = [],
        OrganizationRepository $organizationRepository
    ) {
        parent::__construct($dataClass, $validationGroups);

        $this->organizationRepository = $organizationRepository;
    }

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
            ->add('expiredAt', Type\DateTimeType::class, [
                'widget'=>'single_text',
                'label'    => 'pintushi.organization.expired_at.label',
            ])
            ->add('enabled', Type\CheckboxType::class,  [
                'required' => true,
                'label'    => 'pintushi.organization.enabled.label',
                'choices'  => ['Active' => 1, 'Inactive' => 0]
            ])
            ->add('description', Type\TextareaType::class, [
                'required' => false,
                'label'    => 'pintushi.organization.description.label'
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $globalOrganization = $this->organizationRepository->getGlobalOrganization();

            $addGlobal = false;
            if (null === $globalOrganization) {
                $addGlobal = true;
            } else if ($globalOrganization->getId() === $data->getId()) {
                $addGlobal = true;
            }

            if ($addGlobal) {
                $form->add('global', CheckboxType::class);
            }
        });
    }

     /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_organization';
    }
}
