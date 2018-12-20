<?php

namespace Pintushi\Bundle\OrganizationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Pintushi\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Pintushi\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationFixture extends Fixture implements OrderedFixtureInterface
{
    const MAIN_BUSINESS_UNIT = 'Main';
    const MAIN_ORGANIZATION ='Main';

    const SECOND_ORGANIZATION = 'Second';
    const SECOND_BUSINESS_UNIT = 'Second';

    private $entityManager;

    private $faker;

    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->faker = \Faker\Factory::create('zh_CN');
    }

    public function load(ObjectManager $manager)
    {
        $this->create(self::MAIN_BUSINESS_UNIT, self::MAIN_BUSINESS_UNIT, 'main_organization', 'main_business_unit');
        $this->create(self::SECOND_ORGANIZATION, self::SECOND_BUSINESS_UNIT, 'second_organization', 'second_business_unit');

        $this->entityManager->flush();
    }

    public function create($organizationName, $buName, $orgReference, $buReference)
    {
        $businessUnit = new BusinessUnit();
        $businessUnit->setName($organizationName);

        $this->entityManager->persist($businessUnit);

        $organization = new Organization();
        $organization->setName($buName);
        $organization->setSubdomain($organizationName);
        $organization->enable();
        $organization->addBusinessUnit($businessUnit);

        $businessUnit->setOrganization($organization);

        $this->entityManager->persist($organization);

        $this->setReference($orgReference, $organization);
        $this->setReference($buReference, $businessUnit);
    }

    public function getOrder()
    {
        return  10;
    }
}
