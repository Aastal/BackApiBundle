<?php

namespace Geoks\AdminBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Export
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getContentType() {
        return 'text/csv';
    }

    /**
     * @param $entities
     * @param array $fields
     * @return Response
     */
    public function export($entities, array $fields)
    {
        if (!$entities) {
            throw new NotFoundHttpException("No Data");
        }

        $name = (new \ReflectionClass($entities[0]))->getShortName();

        $now = new \DateTime();
        $rootRacine = $this->container->get('kernel')->getRootDir() . '/../web/exports';
        $filenameWeb = '/export-' . strtolower($name) . '(' . $now->format('d-m-Y-H:i') . ').csv';

        $handle = fopen($rootRacine . $filenameWeb, 'w+');
        fputcsv($handle, $fields, ";");

        foreach ($entities as $entity) {
            $values = [];

            foreach ($fields as $f) {
                $f = ucfirst(str_replace('_', "", $f));

                $value = $entity->{'get' . ucfirst(str_replace(' ', "", $f))}();

                if ($value instanceof \DateTime) {
                    $values[] = $value->format('d/m/Y H:i:s');
                } elseif (is_array($value)) {
                    $values[] = implode(',', $value);
                } else {
                    $values[] = $value;
                }
            }

            fputcsv($handle, $values, ";");
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}