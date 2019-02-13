<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\PersistentCollection;
use Pintushi\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Pintushi\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationsSelectType extends AbstractType
{
    /** @var  EntityManager */
    protected $em;

    /** @var BusinessUnitManager */
    protected $buManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param EntityManager          $em
     * @param BusinessUnitManager    $buManager
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        ObjectManager $em,
        BusinessUnitManager $buManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->em = $em;
        $this->buManager = $buManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'                   => 'pintushi.user.business_units.label',
                'configs'                 => [
                    'is_safe'              => false,
                ],
                'organization_tree_ids'   => [],
                'selected_organizations'  => [],
                'selected_business_units' => [],
                'inherit_data'            => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'pintushi_organizations_select';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();

                if (!empty($data['organizations'])) {
                    $organizations = json_decode(reset($data['organizations']), true);

                    if (!$organizations['organizations'] && !empty($data['businessUnits'])) {
                        $data['organizations'] = [$this->tokenAccessor->getOrganizationId()];
                    } else {
                        $data['organizations'] = $organizations['organizations'];
                    }
                }

                $event->setData($data);
            }
        );

        $this->addOrganizationsField($builder);
        $builder->add(
            'businessUnits',
            BusinessUnitSelectAutocomplete::class,
            [
                'required' => false,
                'label' => 'pintushi.user.form.business_units.label',
                'autocomplete_alias' => 'business_units_tree_search_handler',
                'configs'            => [
                    'multiple'    => true,
                    'component'   => 'bu-tree-autocomplete',
                    'placeholder' => 'pintushi.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $buTree = $this->buManager->getBusinessUnitRepo()->getOrganizationBusinessUnitsTree(
            $this->tokenAccessor->getOrganizationId()
        );

        $view->vars['organization_tree_ids'] = $buTree;

        /** @var PersistentCollection $businessUnitData */
        $businessUnitData = $view->vars['data']->getBusinessUnits();
        if ($businessUnitData) {
            $businessUnitData = $businessUnitData->map(
                function ($item) {
                    return $item->getId();
                }
            )->getValues();
        }

        $view->vars['default_organization'] = $this->tokenAccessor->getOrganizationId();
        $view->vars['selected_organizations']  = [$this->tokenAccessor->getOrganizationId()];
        $view->vars['selected_business_units'] = $businessUnitData;
        $view->vars['accordion_enabled'] = $this->buManager->getTreeNodesCount($buTree) > 1000;
    }

    /**
     * Adds organizations field to form
     *
     * @param FormBuilderInterface $builder
     */
    protected function addOrganizationsField(FormBuilderInterface $builder)
    {
        $builder->add(
            'organizations',
            EntityType::class,
            [
                'class'    => Organization::class,
                'choice_label' => 'name',
                'multiple' => true
            ]
        );
    }

    /**
     * @return User
     */
    protected function getLoggedInUser()
    {
        return $this->tokenAccessor->getUser();
    }
}
