<?php

namespace Pintushi\Bundle\OrganizationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Resolve organization from query string
 */
class OrganizationFormSubscriber implements EventSubscriberInterface
{
    public const QUERY_ID= '_org_id';

    protected $organizationField;
    protected $requestStack;
    protected $organizationRepository;
    protected $propertyAccessor;

    public function __construct(
        $organizationField,
        RequestStack $requestStack,
        OrganizationRepository $organizationRepository,
        PropertyAccessor $propertyAccessor
    ) {
        $this->organizationField = $organizationField;
        $this->requestStack = $requestStack;
        $this->organizationRepository = $organizationRepository;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData'
        );
    }

     /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $entity = $event->getData();

        $organizationId = $this->requestStack->getMasterRequest()->query->get(self::QUERY_ID);
        if (!$entity->getId() && $organizationId && $organization = $this->organizationRepository->find($organizationId)) {
            $this->propertyAccessor->setValue($entity, $this->organizationField, $organization);
        }
    }
}
