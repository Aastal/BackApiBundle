<?php

namespace Geoks\AdminBundle\Controller;

use Geoks\AdminBundle\Controller\Interfaces\AdminControllerInterface;
use Geoks\AdminBundle\Controller\Traits\AdminTrait;
use Geoks\AdminBundle\Form\Export\ExportType;
use Geoks\AdminBundle\Form\Import\ImportImageType;
use Geoks\AdminBundle\Form\Import\ImportType;
use Geoks\ApiBundle\Controller\Traits\ApiResponseTrait;
use Geoks\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

abstract class AdminController extends Controller implements AdminControllerInterface
{
    use AdminTrait;
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
    protected function getUserRepository()
    {
        return $this->getParameter('geoks_api.user_class');
    }

    /**
     * @return string
     */
    protected function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    protected function getEntityRepository()
    {
        return $this->entityRepository;
    }

    /**
     * @return array
     */
    protected function getFieldsExport()
    {
        return $this->fieldsExport;
    }

    /**
     * @return string
     */
    protected function getAdminBundle()
    {
        return $this->getParameter('geoks_admin.local_bundle');
    }

    protected function getEntityView()
    {
        $fs = new Filesystem();
        $root = null;

        if ($fs->exists($this->getParameter('kernel.root_dir') . "/../src/" . $this->getAdminBundle() . '/Resources/views/' . $this->className)) {
            $root = $this->getAdminBundle() . ':' . $this->className;
        } elseif ($fs->exists($this->getParameter('kernel.root_dir') . "/../src/Geoks/AdminBundle/Resources/views/" . $this->className)) {
            $root = 'Geoks' . $this->getAdminBundle() . ':' . $this->className;
        }

        if (!$root) {
            throw new \Exception("No Template for class : " . $this->className);
        }

        return $root;
    }

    /**
     * @return string
     */
    protected function getFormFilter()
    {
        if (!$this->formFilter) {
            $this->formFilter = "Geoks\\AdminBundle\\Form\\Export\\ExportType";
        }

        return $this->formFilter;
    }

    /**
     * @return string
     */
    protected function getFormCreate()
    {
        return $this->formCreate;
    }

    /**
     * @return string
     */
    protected function getFormUpdate()
    {
        return $this->formUpdate;
    }

    /**
     * @return string
     */
    protected function getNamePluralize()
    {
        return $this->get("geoks.utils.string_manager")->pluralize(lcfirst($this->className));
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

        if (null !== $router->getRouteCollection()->get('geoks_admin_' . $this->getNamePluralize() . '_import')) {
            $payload['import_form'] = $this->__importForm()->createView();
        }

        if (null !== $router->getRouteCollection()->get('geoks_admin_' . $this->getNamePluralize() . '_import_image')) {
            $payload['import_image_form'] = $this->__importImageForm()->createView();
        }

        return $this->render($this->getEntityView() . ':index.html.twig', $payload);
    }

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->entityRepository)->find($id);

        $fields = $this->get('geoks_admin.entity_fields')->getFieldsName(get_class($entity));
        $images = $this->get('geoks_admin.entity_fields')->getImageFields($entity);
        $fieldsAssociations = $this->get('geoks_admin.entity_fields')->getFieldsAssociations(get_class($entity));

        return $this->render($this->getEntityView() . ':show.html.twig', [
            "entity" => $entity,
            "fields" => $fields,
            "images" => $images,
            "fields_associations" => $fieldsAssociations
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

            /** @var User $user */
            $user = $em->getRepository($this->getUserRepository())->findOneBy(['email' => $email]);

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

        return $this->render($this->getEntityView() . ':Security:login.html.twig');
    }

    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new $this->entityRepository();

        $form = $this->createForm($this->getFormCreate(), $entity, [
            'attr' => [
                'id' => "app." . lcfirst($this->className),
                'class' => "form-horizontal"
            ],
            'action' => $this->generateUrl(sprintf('geoks_admin_' . $this->getNamePluralize() . '_create')),
            'method' => 'POST',
            'translation_domain' => strtolower($this->className),
            'data_class' => $this->entityRepository,
            'entity_fields' => $this->get('geoks_admin.entity_fields'),
            'translator' => $this->get('translator'),
            'current_user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em->persist($entity);
                $em->flush();

                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'create');

                return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
            } else {
                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'create');
            }
        }

        return $this->render($this->getEntityView() . ':form.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        $changePassword = false;

        if ($request->get('changePassword') == true || $request->get('plainPassword')) {
            $changePassword = true;
        }

        $form = $this->createForm($this->getFormUpdate(), $entity, [
            'attr' => [
                'id' => "app." . lcfirst($this->className),
                'class' => "form-horizontal"
            ],
            'action' => $this->generateUrl(sprintf('geoks_admin_' . $this->getNamePluralize() . '_update'), ['id' => $id]),
            'method' => 'PATCH',
            'translation_domain' => strtolower($this->className),
            'data_class' => $this->entityRepository,
            'change_password' => $changePassword,
            'entity_fields' => $this->get('geoks_admin.entity_fields'),
            'translator' => $this->get('translator'),
            'current_user' => $this->getUser()
        ]);

        $form->remove('password');

        if ($request->isMethod('PATCH')) {

            $form->submit($request->request->get($form->getName()), false);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {

                    if ($changePassword) {
                        $entity->setPassword($this->encodeUserPassword($entity, $entity->getPlainPassword()));
                    }

                    $em->flush();

                    $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'update');

                    return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
                }

                $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'update');
            }
        }

        return $this->render($this->getEntityView() . ':form.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity
        ]);
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->entityRepository)->find($id);

        if (!$entity) {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'delete');

            return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
        }

        $em->remove($entity);
        $em->flush();

        $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'delete');

        return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
    }

    public function importAction(Request $request)
    {
        $form = $this->__importForm();
        $form->handleRequest($request);

        $result = $this->container->get('geoks_admin.import')->importFromCsv($form, $this->getEntityRepository());

        if ($result["success"] === true) {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'import');
        } else {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'empty_or_wrong_value');
        }

        return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
    }

    public function importImageAction(Request $request)
    {
        $form = $this->__importImageForm();
        $form->handleRequest($request);

        $result = $this->container->get('geoks_admin.import')->importImages($form, $this->getEntityRepository());

        if ($result["success"] === true) {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(true, 'import');
        } else {
            $this->container->get('geoks.flashbag.handler')->setFormFlashBag(false, 'empty_or_wrong_value');
        }

        return $this->redirect($this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_index'));
    }

    public function searchAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Ajax only');
        }

        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository($this->entityRepository)->search($request->get('data'));

        return new JsonResponse($entities);
    }

    private function __importForm()
    {
        $form = $this->createForm(ImportType::class, null, [
            'action' => $this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_import'),
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

    private function __importImageForm()
    {
        $form = $this->createForm(ImportImageType::class, null, [
            'action' => $this->generateUrl('geoks_admin_' . $this->getNamePluralize() . '_import_image'),
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

    public function multipleDeleteAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Ajax only');
        }

        $em = $this->getDoctrine()->getManager();

        foreach ($request->get("ids") as $id) {
            $entity = $em->getRepository($this->entityRepository)->find($id);
            $em->remove($entity);
        }

        $em->flush();

        return new JsonResponse(["success" => true]);
    }

    public function dataExportAction(Request $request)
    {
        $entities = [];
        $em = $this->getDoctrine()->getManager();

        foreach ($request->get("datas") as $id) {
            $entities[] = $em->getRepository($this->entityRepository)->find($id);
        }

        $dumper = $this->get('geoks_admin.export');
        $now = new \DateTime();

        $response = new Response($dumper->export($entities, $this->fieldsExport));
        $filenameWeb = 'export-' . strtolower($this->className) . '(' . $now->format('d-m-Y-H:i') . ').csv';

        $response->headers->set('Content-Type', $dumper->getContentType());
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filenameWeb));

        return new JsonResponse(["success" => $filenameWeb]);
    }
}
