<?php

declare(strict_types=1);

namespace OCA\ClubSuiteCore\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class MemberMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'clubsuite_core_members', Member::class);
    }

    /**
     * @return Member[]
     */
    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('clubsuite_core_members')
           ->orderBy('lastname', 'ASC')
           ->addOrderBy('firstname', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findById(int $id): Member {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('clubsuite_core_members')
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

        return $this->findEntity($qb);
    }

    /**
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUserId(string $userId): ?Member {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('clubsuite_core_members')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }
}
