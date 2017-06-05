<?php

namespace Geoks\AdminBundle\Controller;

use Geoks\AdminBundle\Controller\Interfaces\AdminControllerInterface;
use Geoks\AdminBundle\Form\Export\ExportType;
use Geoks\AdminBundle\Form\Import\ImportType;
use Geoks\ApiBundle\Controller\Traits\ApiResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

abstract class AdminController extends Controller implements AdminControllerInterface
{
    use ApiResponseTrait;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $entityRepository;

    /**
     * @var array
     */
    private $fieldsExport;

    /**
     * @var string
     */
    private $formFilter;

    /**
     * @var string
     */
    private $formCreate = "Geoks\\AdminBundle\\Form\\Basic\\CreateForm";

    /**
     * @var string
     */
    private $formUpdate = "Geoks\\AdminBundle\\Form\\Basic\\UpdateForm";

    /**
     * @return string
     */
    public function getUserRepository()
    {
        return $this->getParameter('geoks_api.user_class');
    }

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
     * @return array
     */
    public function getFieldsExport()
    {
        return $this->fieldsExport;
    }

    /**
     * @return string
     */
    public function getAdminBundle()
    {
        return $this->getParameter('geoks_admin.local_bundle');
    }

    /**
     * @return string
     */
    public function getFormFilter()
    {
        if (!$this->formFilter) {
            $this->formFilter = "Geoks\\AdminBundle\\Form\\Export\\ExportType";
        }

        return $this->formFilter;
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
     * @param null|string $formFilter
     * @param null|string $fieldsExport
     */
    public function __construct($entityRepository = null, $formCreate = null, $formUpdate = null, $formFilter = null, $fieldsExport = null)
    {
        // Entity Naming
        $this->entityRepository = $entityRepository;

        if ($this->entityRepository) {
            $this->className = (new \ReflectionClass($entityRepository))->getShortName();
        }

        // Forms and AdminView
        if ($formCreate) {
            $this->formCreate = $formCreate;
        }

        if ($formUpdate) {
            $this->formUpdate = $formUpdate;
        }

        if ($formFilter) {
            $this->formFilter = $formFilter;
        }

        // Export
        $this->fieldsExport = $fieldsExport;
    }

    public function indexAction(Request $request)
    {
        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));
        $router = $this->container->get('router');
        $payload = $this->sortEntities($request);

        if (isset($payload['export'])) {
            $dumper = $this->get('geoks_admin.export');
            $now = new \DateTime();

            $response = new Response($dumper->export($payload["export"], $this->fieldsExport));
            $filenameWeb = 'export-' . strtolower($this->className) . '(' . $now->format('d-m-Y-H:i') . ').csv';

            $response->headers->set('Content-Type', $dumper->getContentType());
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filenameWeb));

            return $response;
        }

        if (null !== $router->getRouteCollection()->get('geoks_admin_' . $namePluralize . '_import')) {
            $payload['import_form'] = $this->__importForm()->createView();
        }

        return $this->render($this->getAdminBundle() . ':' . $this->className . ':index.html.twig', $payload);
    }

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->entityRepository)->find($id);

        return $this->render($this->getAdminBundle() . ':' . $this->className . ':show.html.twig', [
            "entity" => $entity
        ]);
    }

    public function loginAction()
    {
        return $this->render($this->getAdminBundle() . ':Security:login.html.twig');
    }

    public function loginCheckAction(Request $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');

        if ($email !== null && $password !== null) {
            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository($this->getUserRepository())->findOneByEmail($email);

            if (!$user) {
                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'user.notFound');

                return $this->render($this->getAdminBundle() . ':Security:login.html.twig');
            }

            if ($user && $this->checkUserPassword($user, $password)) {
                if ($user->isEnabled()) {
                    $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername(), true);

                    return $this->redirectToRoute('geoks_admin_index');
                } else {
                    $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'user.disabled');
                }
            } else {
                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'user.login.wrong');
            }
        }

        return $this->render($this->getAdminBundle() . ':Security:login.html.twig');
    }

    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new $this->entityRepository();

        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));

        $form = $this->createForm($this->getFormCreate(), $entity, array(
            'attr' => [
                'id' => "app." . lcfirst($this->className),
                'class' => "form-horizontal"
            ],
            'action' => $this->generateUrl(sprintf('geoks_admin_' . $namePluralize . '_create')),
            'method' => 'POST',
            'translation_domain' => strtolower($this->className),
            'data_class' => $this->entityRepository,
            'service_container' => $this->get('service_container')
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                if ($form->has('plainPassword')) {
                    $entity->setPassword($this->encodeUserPassword($entity, $entity->getPlainPassword()));
                }

                $em->persist($entity);
                $em->flush();

                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'create');

                return $this->redirect($this->generateUrl('geoks_admin_' . $namePluralize . '_index'));
            } else {
                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'create');
            }
        }

        return $this->render($this->getAdminBundle() . ':' . $this->className . ':form.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));
        $changePassword = false;

        if ($request->get('changePassword') == true || $request->get('plainPassword')) {
            $changePassword = true;
        }

        $form = $this->createForm($this->getFormUpdate(), $entity, array(
            'attr' => [
                'id' => "app." . lcfirst($this->className),
                'class' => "form-horizontal"
            ],
            'action' => $this->generateUrl(sprintf('geoks_admin_' . $namePluralize . '_update'), ['id' => $id]),
            'method' => 'PATCH',
            'translation_domain' => strtolower($this->className),
            'data_class' => $this->entityRepository,
            'service_container' => $this->get('service_container'),
            'change_password' => $changePassword
        ));

        $form->remove('password');

        if ($request->getMethod() == 'PATCH') {

            $form->submit($request, true);

            if ($form->isValid()) {

                if ($changePassword) {
                    $entity->setPassword($this->encodeUserPassword($entity, $entity->getPlainPassword()));
                }

                $em->flush();

                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'update');

                return $this->redirect($this->generateUrl('geoks_admin_' . $namePluralize . '_index'));
            }

            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'update');
        }

        return $this->render($this->getAdminBundle() . ':' . $this->className . ':form.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));

        if (!$entity) {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'delete');

            return $this->redirect($this->generateUrl('geoks_admin_' . $namePluralize . '_index'));
        }

        $em->remove($entity);
        $em->flush();

        $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'delete');

        return $this->redirect($this->generateUrl('geoks_admin_' . $namePluralize . '_index'));
    }

    public function importAction(Request $request)
    {
        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));

        $form = $this->__importForm();
        $form->handleRequest($request);

        $result = $this->container->get('geoks_admin.import')->importFromCsv($form->get('import_csv')->getData(), $this->getEntityRepository(), $form->get('type')->getData());

        if ($result["success"] === true) {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'import');
        } else {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'empty_or_wrong_value');
        }

        return $this->redirect($this->generateUrl('geoks_admin_' . $namePluralize . '_index'));
    }

    public function searchAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();

            $entities = $em->getRepository($this->entityRepository)->search($request->get('data'));

            return new JsonResponse($entities);
        } else {
            throw new \Exception('Ajax only');
        }
    }

    private function __importForm()
    {
        $namePluralize = $this->get("geoks.api.pluralization")->pluralize(lcfirst($this->className));

        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('geoks_admin_' . $namePluralize . '_import'),
            'attr' => [
                'id' => "admin.import." . lcfirst($this->className),
                'class' => "form-horizontal"
            ],
            'method' => 'POST',
            'class' => $this->entityRepository,
            'translation_domain' => strtolower($this->className)
        ]);

        return $form;
    }

    protected function sortEntities($request)
    {
        $em = $this->getDoctrine()->getManager();

        $filters = [];
        $payload = [];

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
        $paginator = $this->get('knp_paginator');

        $payload['entities'] = $paginator->paginate(
            $entities,
            ((count($results) / 10) < ($request->query->get('page', 1)-1)) ? 1 : $request->query->get('page', 1),
            10, array('wrap-queries' => true)
        );

        return $payload;
    }

    /**
     *
     * @Route("/entities-remove", name="delete_entities", options={"expose"=true})
     *
     */
    public function multipleDeleteAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();

            foreach ($request->get("ids") as $id) {
                $entity = $em->getRepository($this->getEntityRepository())->find($id);
                $em->remove($entity);
            }

            $em->flush();

            return new JsonResponse(["success"=>true]);
        } else {
            throw new \Exception('Ajax only');
        }
    }
}

