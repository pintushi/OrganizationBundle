<?php

namespace Pintushi\Bundle\OrganizationBundle\EventListener;

use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Videni\Bundle\RestBundle\Event\AfterFormResolveEvent;
use Videni\Bundle\RestBundle\Operation\ActionTypes;
use Symfony\Component\Routing\RouterInterface;
use Pintushi\Bundle\OrganizationBundle\Form\EventListener\OrganizationFormSubscriber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 *  Redirect to organization selector page when create resource, if current organization is global
 */
class AfterFormResolveListener
{
      /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    protected $doctrineHelper;

    protected $tokenAccessor;

    protected $router;

    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        DoctrineHelper $doctrineHelper,
        TokenAccessorInterface $tokenAccessor,
        RouterInterface $router
    )  {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->router = $router;
    }

    public function onFormResolved(AfterFormResolveEvent $event)
    {
        $context = $event->getContext();
        $form = $event->getForm();
        $request = $event->getRequest();

        if (ActionTypes::CREATE === $context->getAction() &&
            in_array($request->getMethod(), ['POST', 'PUT', 'PATCH']) &&
            $this->tokenAccessor->getOrganization()->isGlobal() &&
            $this->hasOwnershipMetadata(get_class($event->getData())) &&
            !$request->query->has(OrganizationFormSubscriber::QUERY_ID)
        ) {
            $event->setResponse(
                new JsonResponse([
                        'redirect' => $this->router->generate('api_organizations_select_organization')
                    ],
                    Response::HTTP_TEMPORARY_REDIRECT
                )
            );
        }
    }

     /**
     * Get metadata for entity
     *
     * @param object|string $entity
     *
     * @return bool|OwnershipMetadataInterface
     * @throws \LogicException
     */
    protected function hasOwnershipMetadata($entity)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }
        if (!$this->doctrineHelper->isManageableEntity($entity)) {
            return false;
        }

        $metadata = $this->ownershipMetadataProvider->getMetadata($entity);

        return $metadata->hasOwner();
    }
}
