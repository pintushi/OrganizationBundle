<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pintushi\Bundle\FormBundle\Form\Type\SelectHiddenAutocompleteType;
use Pintushi\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormBuilderInterface;

class OrganizationSelectType extends AbstractType
{
     /** @var EntityManager */
    protected $entityManager;


    /** @var string */
    protected $entityClass;

    /**
     * @param EntityManager $entityManager
     * @param $entityClass
     */
    public function __construct(
        EntityManager $entityManager,
        $entityClass
    ) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
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
        }
    }

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
                'autocomplete_alias' => 'organization'
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
