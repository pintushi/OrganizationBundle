<?php

declare(strict_types=1);

namespace Pintushi\Bundle\OrganizationBundle\DataPersister;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Pintushi\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Pintushi\Bundle\SecurityBundle\Exception\ForbiddenException;
use Videni\Bundle\RestBundle\EventListener\DataPersister as BaseDataPersister;
use Videni\Bundle\RestBundle\Util\DoctrineHelper;

/**
 * Check owner assignment when remove resource
 */
class DataPersister extends BaseDataPersister
{
    private $managerRegistry;

    private $ownerDeletionManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ManagerRegistry $managerRegistry,
        OwnerDeletionManager $ownerDeletionManager
    )  {
        parent::__construct($doctrineHelper);
        $this->managerRegistry = $managerRegistry;
        $this->ownerDeletionManager = $ownerDeletionManager;

    }

    public function remove($data)
    {
        if(null === $data) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($data, false);
        if (!$em) {
            // only manageable entities are supported
            return;
        }

        if (\is_array($data) || $data instanceof \Traversable) {
            $em->getConnection()->beginTransaction();
            try {
                foreach ($data as $entity) {
                    $this->checkPermissions($data);
                    $em->remove($entity);
                }
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                throw $e;
            }
        } else {
            $this->checkPermissions($data);
            $em->remove($data);
        }
    }

      /**
     * Checks if a delete operation is allowed
     *
     * @param object        $entity
     * @throws ForbiddenException if a delete operation is forbidden
     */
    protected function checkPermissions($entity)
    {
        if ($this->ownerDeletionManager->isOwner($entity) && $this->ownerDeletionManager->hasAssignments($entity)) {
            throw new ForbiddenException('Can\'t delete owner which has assignments');
        }
    }
}
