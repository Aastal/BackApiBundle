<?php

namespace Geoks\ApiBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * GlobalRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
abstract class GlobalRepository extends EntityRepository
{
    /**
     * Filter function for admin listing
     *
     * @param array $parameters
     *
     * @return \Doctrine\ORM\Query
     */
    public function filterBy(array $parameters = [])
    {
        /**
         * Param closure, check if notification has given key search
         *
         * @param $queryBuilder
         * @param $key
         * @param $search
         * @param $i
         */
        $filter = function (&$queryBuilder, $key, $search, $i) {
            if (is_bool($search)) {
                $joinLetter = strtolower(substr($key, -3)) . rand(1, 100);

                if ($search === true) {
                    $queryBuilder
                        ->innerJoin('a.' . $key, $joinLetter);
                }
            } elseif (is_object($search) && !$search instanceof \DateTime) {
                $joinLetter = strtolower(substr($key, -3));

                $queryBuilder
                    ->andWhere('a.' . $key . ' = ' .  ':search' . $i)
                    ->leftJoin('a.' . $key, $joinLetter)
                    ->setParameter(':search' . $i, $search)
                ;
            } elseif ($search instanceof \DateTime) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->between('a.' . $key, ':dateMin', ':dateMax'))
                    ->setParameter(':dateMin', $search->modify('-1 hour')->format('Y-m-d H:i'))
                    ->setParameter(':dateMax', $search->modify('+1 hour')->format('Y-m-d H:i'))
                ;
            } elseif ($search !== null) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->like('a.' . $key, ':search' . $i))
                    ->setParameter(':search' . $i, "%" . $search . "%")
                ;
            }
        };

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder('a');

        $i = 0;
        foreach ($parameters as $key => $value) {
            $filter($queryBuilder, $key, $value, $i++);
        }

        return $queryBuilder->getQuery();
    }
}