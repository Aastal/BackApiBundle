<?php

namespace Geoks\AdminBundle\Services;

use Doctrine\ORM\PersistentCollection;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class Export
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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
            return null;
        }

        $name = (new \ReflectionClass($entities[0]))->getShortName();

        $now = new \DateTime();
        $rootRacine = $this->kernel->getRootDir() . '/../web/exports';
        $filenameWeb = '/export-' . strtolower($name) . '(' . $now->format('d-m-Y-H:i') . ').csv';

        $handle = fopen($rootRacine . $filenameWeb, 'w+');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($handle, $fields, ";");

        foreach ($entities as $entity) {
            $values = [];

            foreach ($fields as $f) {
                $f = ucfirst(str_replace('_', "", $f));
                $id = null;
                $arrayString = null;

                if (strpos($f, '.')) {
                    $arrayString = explode(".", $f);

                    if (strpos($f, '.id')) {
                        $id = end($arrayString);
                    }
                }

                if (isset($arrayString) && method_exists($entity, 'get' . ucfirst(str_replace(' ', "", $arrayString[0])))) {
                    $value = $entity->{'get' . ucfirst(str_replace(' ', "", $arrayString[0]))}();
                } elseif (method_exists($entity, 'get' . ucfirst(str_replace(' ', "", $f)))) {
                    $value = $entity->{'get' . ucfirst(str_replace(' ', "", $f))}();
                } else {
                    $value = $entity->{'is' . ucfirst(str_replace(' ', "", $f))}();
                }

                if (is_bool($value)) {
                    if ($value) {
                        $values[] = 1;
                    } else {
                        $values[] = 0;
                    }
                } elseif (!$value) {
                    $values[] = null;
                } elseif ($value instanceof \DateTime) {
                    $values[] = $value->format('d/m/Y H:i:s');
                } elseif (is_array($value)) {
                    $values[] = implode(',', $value);
                } elseif ($value instanceof PersistentCollection) {
                    if (isset($value->toArray()[0]) && !$id) {
                        $values[] = implode(",", $value->toArray());
                    } elseif (isset($value->toArray()[0]) && $id) {

                        foreach ($value->toArray() as $v) {
                            $values[$f][] = $v->getId();
                        }

                        $values[$f] = implode(",", $values[$f]);
                    } else {
                        $values[] = null;
                    }
                } else {
                    $values[] = (string) $value;
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
