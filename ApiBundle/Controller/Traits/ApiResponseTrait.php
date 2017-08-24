<?php

namespace Geoks\ApiBundle\Controller\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Geoks\AdminBundle\Form\Export\ExportType;
use Geoks\UserBundle\Entity\User;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Form;
use Geoks\ApiBundle\Services\Serializer;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

trait ApiResponseTrait
{
    /**
     * Serialize and return JsonResponse of params.
     * If the first param is \Traversable (a Class like User), you should define the context group in the key.
     *
     * @param string|array|Form $data
     * @param integer $status
     * @return JsonResponse
     */
    protected function serializeResponse($data, $status = 200)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('geoks.api.serializer');

        // Success
        if ($status == Response::HTTP_OK) {
            return new JsonResponse($serializer->serializeData($data), $status);
        }

        // Parse Error
        if ($data instanceof Form) {
            $results = ['error' => $this->formErrorsToArray($data)];
        } elseif (is_array($data)) {
            $results = [];

            foreach ($data as $key => $value) {
                $results += [$key => $value];
            }
        } else {
            $results = ['error' => $data];
        }

        return new JsonResponse($results, $status);
    }

    /**
     * Serialize and return JsonResponse of params but don't rename the json key.
     * If the first param is \Traversable (a Class like User), you should define the context group in the key.
     *
     * @param string|array|Form $data
     * @param integer $status
     * @return JsonResponse
     */
    protected function simpleSerializeResponse($data, $status = 200)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('geoks.api.serializer');

        // Success
        if ($status == Response::HTTP_OK) {
            return new JsonResponse($serializer->simpleSerializeData($data), $status);
        }

        // Parse Error
        if ($data instanceof Form) {
            $results = ['error' => $this->formErrorsToArray($data)];
        } elseif (is_array($data)) {
            $results = [];

            foreach ($data as $key => $value) {
                $results += [$key => $value];
            }
        } else {
            $results = ['error' => $data];
        }

        return new JsonResponse($results, $status);
    }

    /**
     * Get form errors in key value array
     *
     * @param \Symfony\Component\Form\Form $form
     * @param boolean $first
     * @return array
     */
    protected function formErrorsToArray($form, $first = true)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            if ($first) {
                $errors[$error->getOrigin()->getName()][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $key => $child) {
            if ($err = $this->formErrorsToArray($child, false)) {
                $errors[$key] = $err;
            }
        }

        return $errors;
    }

    /**
     *
     * @param User $user
     * @param string $password
     * @return boolean
     */
    protected function checkUserPassword($user, $password)
    {
        /** @var EncoderFactory $factory */
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);

        if (!$encoder) {
            return false;
        }

        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    /**
     * @param $user
     * @param $raw
     * @return mixed
     */
    protected function encodeUserPassword($user, $raw)
    {
        /** @var UserPasswordEncoder $encoder */
        $encoder = $this->get('security.password_encoder');

        return $encoder->encodePassword($user, $raw);
    }

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
