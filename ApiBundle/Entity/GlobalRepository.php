<?php

namespace Geoks\ApiBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * GlobalRepository
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
        $joins = $this->getClassMetadata()->getAssociationMappings();

        /**
         * @param QueryBuilder $queryBuilder
         * @param string $key
         * @param $search
         * @param integer $i
         * @param array $joins
         */
        $filter = function (&$queryBuilder, $key, $search, $i, &$joins) {
            if (is_bool($search)) {

                if ($search === true && isset($joins[$key])) {
                    $queryBuilder
                        ->innerJoin('a.' . $key, $key);
                } elseif ($search === true) {
                    $queryBuilder
                        ->andWhere('a.' . $key . ' = 1');
                }
            } elseif (is_object($search) && !$search instanceof \DateTime) {

                $queryBuilder
                    ->andWhere($key  .'.id = ' .  ':search' . $i)
                    ->leftJoin('a.' . $key, $key)
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
            $filter($queryBuilder, $key, $value, $i++, $joins);
        }

        return $queryBuilder->select('a')->orderBy('a.id', 'DESC')->getQuery();
    }
}
