<?php

namespace Pintushi\Bundle\OrganizationBundle\EventListener;

use Pintushi\Bundle\SecurityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Pintushi\Bundle\OrganizationBundle\Form\Extension\OrganizationFormExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\HttpFoundation\Request;
use Pintushi\Bundle\OrganizationBundle\Provider\RequestBasedOrganizationProvider;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Videni\Bundle\RestBundle\Context\ResourceContextStorage;
use Videni\Bundle\RestBundle\Operation\ActionTypes;

/**
 * Resolve organization for ownership resource
 */
class ResourceOrganizationListener
{
    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    protected $doctrineHelper;

    protected $router;

    protected $authorizationChecker;

    protected $propertyAccessor;

    protected $resourceContextStorage;

    protected $requestBasedOrganizationProvider;

    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        ResourceContextStorage $resourceContextStorage,
        RequestBasedOrganizationProvider $requestBasedOrganizationProvider
    )  {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->resourceContextStorage = $resourceContextStorage;
        $this->requestBasedOrganizationProvider = $requestBasedOrganizationProvider;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if(!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->get('ownership_disabled', false)) {
            return;
        }

        $context = $this->resourceContextStorage->getContext();
        if (null == $context) {
            return;
        }
        if (ActionTypes::CREATE !== $context->getAction()) {
            return;
        }

        if (!$request->attributes->has('data')) {
            return;
        }

        $data = $request->attributes->get('data');
        $metadata = $this->hasOwnershipMetadata(get_class($data));
        if (!$metadata ||!$metadata->hasOwner()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('CREATE', 'entity:'.get_class($data))) {
            return;
        }

        if ($this->requestBasedOrganizationProvider->isViewPermissionGrantedOnOrganizationClass()) {
            if (!$this->requestBasedOrganizationProvider->hasIdentifier()) {
                $event->setResponse(
                     new JsonResponse([
                            'redirect' => $this->router->generate('api_organizations_select_organization')
                        ],
                        Response::HTTP_TEMPORARY_REDIRECT
                    )
                );

                return;
            }

            $this->getPropertyAccessor()->setValue(
                $data,
                $metadata->getOrganizationFieldName(),
                $this->requestBasedOrganizationProvider->getOrganizationFromRequest()
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

       return $this->ownershipMetadataProvider->getMetadata($entity);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
