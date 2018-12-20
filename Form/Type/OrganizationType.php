<?php

declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\Form\Type;

use Pintushi\Bundle\PromotionBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Pintushi\Bundle\ShippingBundle\Form\Type\ShippingMethodCollectionType;
use Pintushi\Bundle\ShippingBundle\Form\Type\ShippingMethodType;
use Pintushi\Bundle\AddressBundle\Form\Type\AddressType;

final class OrganizationType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'pintushi.form.organization.name',
            ])
            ->add('subdomain', TextType::class, [
                'label' => 'pintushi.form.organization.subdomain',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'pintushi.form.organization.enabled',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'pintushi.form.organization.description',
            ])
            ->add('expiredAt', DateTimeType::class, [
                'label' => 'pintushi.form.organization.expires_at',
                'widget'=>'single_text',
                'format' => 'Y-m-d H:i:s',
            ])
            ->add('logo', TextType::class, array(
                'label' => 'pintushi.form.organization.logo',
            ))
            ->add('address', AddressType::class, array(
                'label'=>'pintushi.form.organization.address',
                'error_bubbling' => false,
            ))
            ->add('shippingMethods', ShippingMethodCollectionType::class, [
                'entry_type' => ShippingMethodType::class,
                'label' => 'pintushi.form.organization.shipping_methods'
            ]);
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'pintushi_organization';
    }
}
