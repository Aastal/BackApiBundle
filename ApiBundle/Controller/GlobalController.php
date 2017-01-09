<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Controller\Interfaces\GlobalControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Geoks\UserBundle\Entity\User;

/**
 * Class GlobalController
 *
 * Default CRUD of any entity
 */
abstract class GlobalController extends ApiController implements GlobalControllerInterface
{
    protected $className = '';
    protected $entityRepository = '';

    protected $formCreate = "";
    protected $formUpdate = "";

    public function getAll($secure = null)
    {
        if ($secure) {
            if (!$this->getUser()) {
                return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
            }

            if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
            }
        }

        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository($this->entityRepository)->findAll();

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOne($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $entity]);
    }

    public function getAllByUser()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($this->entityRepository)->findBy(array('user' => $this->getUser()));

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOneByUser($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->findOneBy(array(
            'id' => $id,
            'user' => $this->getUser()
        ));

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $entity]);
    }

    /**
     * Criteria entityName => value
     *
     * @param array $criteria
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAllByCriteria(array $criteria)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($this->entityRepository)->findBy($criteria);

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOneByCriteria($id, array $criteria)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $criteria['id'] = $id;

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->findOneBy($criteria);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $entity]);
    }

    public function getOneByRelation(array $relation = null)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->findOneBy($relation);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $entity]);
    }

    public function create(Request $request, $customSetters = [])
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = new $this->entityRepository();

        $formCreate = new $this->formCreate($this->container, $this->entityRepository);

        $form = $this->createForm($formCreate, $entity, ['method' => $request->getMethod()]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($customSetters as $key => $value) {
                $entity->{'set' . $key}($value);
            }

            $em->persist($entity);
            $em->flush();

            return $this->serializeResponse(['details' => $entity]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function update(Request $request, $id, $customSetters = [])
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        if (!$entity) {
            return $this->serializeResponse('geoks.entity.notFound', Response::HTTP_NOT_FOUND);
        }

        $formUpdate = new $this->formUpdate($this->container, $this->entityRepository);

        $form = $this->createForm($formUpdate, $entity, ['method' => $request->getMethod()]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($customSetters as $key => $value) {
                $entity->{'set' . $key}($value);
            }

            $em->flush();

            return $this->serializeResponse(['details' => $entity]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function delete($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        $em->remove($entity);
        $em->flush();

        return $this->serializeResponse("geoks.entity.deleted");
    }
}