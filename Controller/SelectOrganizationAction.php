<?php

namespace Pintushi\Bundle\OrganizationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Limenius\Liform\Liform;
use Pintushi\Bundle\OrganizationBundle\Form\Type\OrganizationSelectorType;
use Symfony\Component\Routing\Annotation\Route;

class SelectOrganizationAction extends Controller
{
    protected $liform;
    protected $formFactory;

    public function __construct(Liform $liform, FormFactoryInterface $formFactory)
    {
        $this->liform = $liform;
        $this->formFactory = $formFactory;
    }

     /**
     * @Route(
     *  name="api_organizations_select_organization",
     *  methods={"GET"},
     *  path="/organization/selector"
     * )
     */
    public function __invoke()
    {
       $form = $this->formFactory->createNamed('', OrganizationSelectorType::class, [], [
            'csrf_protection' => false
        ]);

       return new JsonResponse(['form_schema' => $this->liform->transform($form)]);
    }
}
