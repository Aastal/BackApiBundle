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
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $entityRepository;

    /**
     * @var string
     */
    private $formCreate = "Geoks\\ApiBundle\\Form\\Basic\\CreateForm";

    /**
     * @var string
     */
    private $formUpdate = "Geoks\\ApiBundle\\Form\\Basic\\UpdateForm";

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getEntityRepository()
    {
        return $this->entityRepository;
    }

    /**
     * @return string
     */
    public function getFormCreate()
    {
        return $this->formCreate;
    }

    /**
     * @return string
     */
    public function getFormUpdate()
    {
        return $this->formUpdate;
    }

    /**
     * AdminController constructor.
     *
     * @param null|string $entityRepository
     * @param null|string $formCreate
     * @param null|string $formUpdate
     */
    public function __construct($entityRepository = null, $formCreate = null, $formUpdate = null)
    {
        // Entity Naming
        $this->entityRepository = $entityRepository;

        if ($this->getEntityRepository()) {
            $this->className = (new \ReflectionClass($entityRepository))->getShortName();
        }

        // Forms
        if ($formCreate) {
            $this->formCreate = $formCreate;
        }

        if ($formUpdate) {
            $this->formUpdate = $formUpdate;
        }
    }

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

        $entities = $em->getRepository($this->getEntityRepository())->findAll();

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOne($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->find($id);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $entity]);
    }

    public function getOneCustom($id, $group)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->find($id);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse([$group => $entity]);
    }

    public function getAllByUser()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($this->getEntityRepository())->findBy(array('user' => $this->getUser()));

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOneByUser($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->findOneBy(array(
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
        $entities = $em->getRepository($this->getEntityRepository())->findBy($criteria);

        return $this->serializeResponse(['list' => $entities]);
    }

    /**
     * @param Request $request
     * @param array $criteria
     * @param array $order
     * @param integer $limit
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAllByCriteriaOrderAndPagination(Request $request, array $criteria = [], array $order = ["id" => "DESC"], $limit = 10)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $page = $request->get('page', 1);

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($this->getEntityRepository())->findBy($criteria, $order, $limit, ($page - 1) * $limit);

        return $this->serializeResponse(['list' => $entities]);
    }

    public function getOneByCriteria($id, array $criteria)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $criteria['id'] = $id;

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->findOneBy($criteria);

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
        $entity = $em->getRepository($this->getEntityRepository())->findOneBy($relation);

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

        $form = $this->createForm($this->getFormCreate(), $entity, [
            'attr' => [
                'id' => "app." . lcfirst($this->className)
            ],
            'method' => 'POST',
            'data_class' => $this->getEntityRepository(),
            'service_container' => $this->get('service_container')
        ]);

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

    public function import(Request $request)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $result = $this->container->get('geoks_admin.import')->importFromCsv($request->files->get('import'), $this->getEntityRepository(), $request->get('type'));

        if ($result["success"] === true) {
            return $this->serializeResponse(["success" => true]);
        }

        return $this->serializeResponse(["success" => false], Response::HTTP_BAD_REQUEST);
    }

    public function update(Request $request, $id, $customSetters = [])
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->find($id);

        if (!$entity) {
            return $this->serializeResponse('geoks.entity.notFound', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm($this->getFormUpdate(), $entity, [
            'method' => $request->getMethod(),
            'data_class' => $this->getEntityRepository(),
            'service_container' => $this->get('service_container'),
            'change_password' => false
        ]);

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

    public function updateMe(Request $request, $id, $customSetters = [])
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->findOneBy([
            "id" => $id,
            "user" => $this->getUser()->getId()
        ]);

        if (!$entity) {
            return $this->serializeResponse('geoks.entity.notFound', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm($this->getFormUpdate(), $entity, [
            'method' => $request->getMethod(),
            'data_class' => $this->getEntityRepository(),
            'service_container' => $this->get('service_container'),
            'change_password' => false
        ]);

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

    public function deleteByUser($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->getEntityRepository())->findOneBy([
            'id' => $id,
            'user' => $this->getUser()
        ]);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        $em->remove($entity);
        $em->flush();

        return $this->serializeResponse(["success" => "geoks.entity.deleted"]);
    }

    public function delete($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getEntityRepository())->find($id);

        if (!$entity) {
            return $this->serializeResponse("geoks.entity.notFound", Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN') && (method_exists($entity, 'getUser') && $entity->getUser() != $this->getUser())) {
            return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
        }

        $em->remove($entity);
        $em->flush();

        return $this->serializeResponse(["success" => "geoks.entity.deleted"]);
    }


}