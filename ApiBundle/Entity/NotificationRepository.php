<?php

namespace Geoks\ApiBundle\Entity;

use Geoks\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * NotificationRepository
 *
 */
class NotificationRepository extends EntityRepository
{
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
     * @return array
     */
    public function getNotificationsByReceiver($user, $limit, $offset)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->where('n.receiver = :userId')
            ->andWhere('n.type IN (1,2)')
            ->setParameter(':userId', $user->getId())
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('n.created_at', 'DESC')
        ;

        $pag = new Paginator($query);
        return $pag->getIterator()->getArrayCopy();    }

    /**
     * @param User $user
     * @return array
     */
    public function getNbUnreadNotifications($user)
    {
        $builder = $this->createQueryBuilder('n');

        $query = $builder
            ->where('n.receiver = :userId')
            ->andWhere('n.type IN (1,2)')
            ->andWhere('n.is_read IS NULL OR n.is_read = 0')
            ->setParameter(':userId', $user->getId())
        ;

        return $query->getQuery()->getScalarResult();
    }
}
