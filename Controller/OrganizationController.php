<?php


declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactoryInterface;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationType;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;
use Pintushi\Bundle\OrganizationBundle\Repository\OrganizationRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\ConstraintViolationList;

class OrganizationController extends Controller
{
    private $organizationRepository;
    private $formFactory;
    private $entityManager;

    public function __construct(
        OrganizationRepository $organizationRepository,
        ObjectManager $entityManager,
        FormFactoryInterface $formFactory
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * @Route(
     *     name="api_admin_update_organization",
     *     path="/organizations/{id}",
     *     methods={"PUT"},
     *     defaults={
     *         "_api_receive"=true,
     *         "_api_resource_class"=Organization::class,
     *         "_api_item_operation_name"="put",
     *     }
     * )
     */
    public function update($data, Request $request)
    {
        return $this->submit($request, $data);
    }

    /**
     * @Route(
     *  name="api_admin_create_organization",
     *  path="/organizations",
     *  methods={"POST"},
     *  defaults={
     *   "_api_receive"=false,
     *   "_api_respond"=true,
     *   "_api_resource_class"= Organization::class,
     *   "_api_collection_operation_name"="post",
     *  }
     * )
     */
    public function create(Request $request)
    {
        $promotion = new Organization();

        return $this->submit($request, $promotion);
    }

    public function submit($request, $organization)
    {
        $form = $this->formFactory->createNamed(
            '',
            OrganizationType::class,
            $organization,
            [
                'csrf_protection' => false,
                'validation_groups' => ['pintushi'],
                'method' => $request->getMethod()
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $organization = $form->getData();

            $em = $this->entityManager;
            $em->persist($organization);
            $em->flush();

            return $organization;
        }

        $violations = new ConstraintViolationList();
        foreach ($form->getErrors(true) as $error) {
            $violations->add($error->getCause());
        }

        return $violations;
    }
}
