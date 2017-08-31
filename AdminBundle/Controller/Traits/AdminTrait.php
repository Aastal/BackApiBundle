<?php

namespace Geoks\AdminBundle\Controller\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Geoks\AdminBundle\Form\Export\ExportType;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

trait AdminTrait
{
    protected function sortEntities(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.default_entity_manager');

        $filters = [];
        $payload = [];

        /** @var Form $form */
        if ($this->getFormFilter() == ExportType::class) {
            $form = $this->createForm($this->getFormFilter(), null, [
                'attr' => [
                    'id' => "app." . lcfirst($this->className)
                ],
                'method' => 'GET',
                'class' => $this->entityRepository,
                'service_container' => $this->get('service_container'),
                'translation_domain' => strtolower($this->className)
            ]);
        } else {
            $form = $this->createForm($this->getFormFilter(), null, [
                'attr' => [
                    'id' => "app." . lcfirst($this->className)
                ],
                'method' => 'GET',
                'translation_domain' => strtolower($this->className)
            ]);
        }

        $form->handleRequest($request);

        // Get filter form data (if submitted)
        $filterDatas = $form->getData();

        /** @var Query $entities */
        $entities = $em->getRepository($this->entityRepository)->filterBy(array_merge($filters, (is_null($filterDatas)) ? array() : $filterDatas));

        // Instanciate the filter view part
        $payload['filter_form'] = $form->createView();

        if ($entities === null) {
            $entities = $em->getRepository($this->entityRepository)->filterBy($filters);
        }

        $results = $entities->getResult();

        if ($form->get('export')->isClicked()) {
            return ["export" => $results];
        }

        // Count the number of results
        $payload['numberOfResults'] = count($results);

        // Paginate query results
        /** @var Paginator $paginator */
        $paginator = $this->get('knp_paginator');

        $payload['entities'] = $paginator->paginate(
            $entities,
            ((count($results) / 10) < ($request->query->get('page', 1)-1)) ? 1 : $request->query->get('page', 1),
            10, array('wrap-queries' => true)
        );

        return $payload;
    }
}
