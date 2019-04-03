<?php

namespace Pintushi\Bundle\OrganizationBundle\Provider;

use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Pintushi\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestBasedOrganizationProvider
{
    const QUERY_ID = '_org_id';

    protected $organizationClass;
    private $organization;
    private $requestStack;

    private $accessGranted = null;

    public function __construct(
        string $organizationClass,
        AuthorizationCheckerInterface $authorizationChecker,
        OrganizationRepository $organizationRepository,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack
    ) {
        $this->organizationClass = $organizationClass;
        $this->authorizationChecker = $authorizationChecker;
        $this->organizationRepository = $organizationRepository;
        $this->tokenAccessor = $tokenAccessor;
        $this->requestStack = $requestStack;
    }

    /**
     * @param  Request $request
     * @return Organization | null
     */
    public function getOrganizationFromRequest()
    {
        if ($this->organization) {
            return $this->organization;
        }

        $organizationId = $this->getRequest()->query->get(self::QUERY_ID);
        if (!$organizationId) {
            return null;
        }

        $this->organization = $this->getOrganizationById($organizationId);

        return $this->organization;
    }

    public function hasIdentifier()
    {
       return $this->getRequest()->query->has(self::QUERY_ID);
    }

    public function isEnabledForAclConditionDataBuilder()
    {
      return $this->getRequest()->attributes->get('_enable_acl_condition_data_builder', false);
    }

    public function isViewPermissionGrantedOnOrganizationClass()
    {
        if( null !== $this->accessGranted) {
            return $this->accessGranted;
        }

        $this->accessGranted = $this->authorizationChecker->isGranted('VIEW', 'entity:'.$this->organizationClass);

        return $this->accessGranted;
    }

    protected function getRequest()
    {
        return $this->requestStack->getMasterRequest();
    }

    protected function getOrganizationById($organizationId)
    {
        $user = $this->tokenAccessor->getUser();
        if(null === $user) {
            return null;
        }

        $userOrganization = $this->tokenAccessor->getOrganization();
        if(null === $userOrganization) {
            return null;
        }

        if (!$this->isViewPermissionGrantedOnOrganizationClass()) {
            return null;
        }

        $organization = null;
        if(!$userOrganization->isGlobal()) {
            $organization = $this->organizationRepository->getEnabledUserOrganizationById($this->tokenAccessor->getUser(), $organizationId)->first();
        } else  {
            $organization = $this->organizationRepository->findOneBy(['id'=> $organizationId, 'enabled' => true]);
        }

        return $organization;
    }
}
