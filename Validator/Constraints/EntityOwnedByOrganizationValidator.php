<?php

namespace Pintushi\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Pintushi\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 *
 * 验证目标属性对象是否属于当前实体的组织。
 * 比如在创建Taxon时，需要确保taxon的parent对象在taxon的组织内。
 *
 */
class EntityOwnedByOrganizationValidator extends ConstraintValidator
{
    private $ownershipMetadataProvider;
    private $authorizationChecker;

    /**
     * @var PropertyAccessor
     */
    private static $propertyAccessor;

    public function __construct(
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity) {
            return;
        }

        $subjectOrganization = $this->getOrganization($entity);
        if (null === $subjectOrganization) {
            return;
        }

        foreach ($constraint->fields as $field) {
            $fieldValue = $this->getPropertyAccessor()->getValue($entity, $field);
            if ($fieldValue === null) {
                continue;
            }
            if ($fieldValue instanceof \Traversable) {
                foreach ($fieldValue as $value) {
                    $this->validateOrganization($field, $value, $subjectOrganization, $constraint);
                }
            } else {
                $this->validateOrganization($field, $fieldValue, $subjectOrganization, $constraint);
            }
        }
    }

    protected function validateOrganization($field, $fieldValue, $organization, $constraint)
    {
        $fieldOrganization = $this->getOrganization($fieldValue);
        if ($fieldOrganization !== $organization) {
             $this->context
                ->buildViolation($constraint->message)
                ->atPath($field)
                ->setParameter('{{organizationName}}', $organization->getName())
                ->setParameter('{{target}}', $field)
                ->addViolation();
        }
    }

    protected function getOrganization($entity)
    {
        if (!is_object($entity)) {
            return null;
        }

        //check special method
        if (class_exists(get_class($entity), 'getOrganization')) {
            return $entity->getOrganization();
        }

        $entityClass = ClassUtils::getClass($entity);
        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        if (null === $ownershipMetadata || !$ownershipMetadata->hasOwner()) {
            return null;
        }
        $organizationFieldName = $ownershipMetadata->getOrganizationFieldName();

        return $this->getPropertyAccessor()->getValue($entity, $organizationFieldName);
    }

     /**
     * @return PropertyAccessor
     */
    private static function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }
}
