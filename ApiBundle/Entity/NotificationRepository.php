<?php

namespace Geoks\ApiBundle\Entity;

use Geoks\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * NotificationRepository
 *
 */
class NotificationRepository extends GlobalRepository
{
    /**
     * @param \DateTime $datetime
     * @return array
     */
    public function getNotificationByDateTime($datetime)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->where('n.created_at <= :dateMin')
            ->setParameters([
                'dateMin' => $datetime,
            ])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * Get last hour notifications
     *
     * @param User $user
     * @return array
     */
    public function getNotificationByTime($user)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->where('n.receiver = :userId')
            ->andWhere('n.type = 4')
            ->andWhere('n.is_read IS NULL OR n.is_read = 0')
            ->andWhere('n.created_at >= :dateMin')
            ->setParameters([
                ':userId' => $user->getId(),
                'dateMin' => new \DateTime('-1 hour'),
            ])
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param $offset
     * @param $limit
     * @param array $types
     * @return array
     */
    public function getNotificationsByReceiver($user, $limit, $offset, $types)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->where('n.receiver = :userId')
            ->andWhere('n.type IN ('. implode(',', $types) . ')')
            ->setParameter(':userId', $user->getId())
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('n.created_at', 'DESC')
        ;

        $pag = new Paginator($query);
        return $pag->getIterator()->getArrayCopy();
    }

    /**
     * @param User $user
     * @param array $types
     * @return array
     */
    public function getNbUnreadNotifications($user, $types)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->select('COUNT(n.id)')
            ->where('n.receiver = :userId')
            ->andWhere('n.type IN ('. implode(',', $types) . ')')
            ->andWhere('n.is_read IS NULL OR n.is_read = 0')
            ->setParameter(':userId', $user->getId())
        ;

        return $query->getQuery()->getSingleScalarResult();
    }
}
