<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Form\Security\ChangePasswordForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserController
 * @package Geoks\ApiBundle\Controller
 *
 * Default CRUD user
 */
abstract class UserController extends ApiController
{
    protected $userRepository = 'Geoks\\UserBundle\\Entity\\User';
    protected $formUpdate = "Geoks\\ApiBundle\\Form\\Basic\\UpdateForm";
    protected $className = 'User';

    public function getAll()
    {
        if (!$this->getUser() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository($this->userRepository)->findBy(array('enabled' => true));

        return $this->serializeResponse(['list' => $users]);
    }

    public function me()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->findOneBy(array('username' => $this->getUser()->getUsername()));

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $user]);
    }

    public function getOne($id)
    {
        if (!$this->getUser() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->find($id);

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse(['details' => $user]);
    }

    public function update(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->find($id);

        if (!$user) {
            return $this->serializeResponse('geoks.user.notFound', Response::HTTP_NOT_FOUND);
        }

        if ($this->getUser()->getUsername() != $user->getUsername() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->serializeResponse('geoks.user.forbidden', Response::HTTP_FORBIDDEN);
        }

        $formUpdate = new $this->formUpdate($this->container, $this->userRepository);

        $form = $this->createForm($formUpdate, $user, ['method' => $request->getMethod()]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setUpdated(new \DateTime());
            $em->flush();

            return $this->serializeResponse(['details' => $user]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function delete($id)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notConnected', Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->userRepository)->find($id);

        if (!$user) {
            return $this->serializeResponse("geoks.user.notFound", Response::HTTP_NOT_FOUND);
        }

        if ($this->getUser() != $user && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->serializeResponse("geoks.user.forbidden", Response::HTTP_FORBIDDEN);
        }

        $em->remove($user);
        $em->flush();

        return $this->serializeResponse("geoks.user.deleted");
    }

    public function changeUserPasswordAction(Request $request)
    {
        $user = $this->getUser();
        $userManager = $this->container->get('fos_user.user_manager');

        $form = $this->createForm(ChangePasswordForm::class);
        $form->handleRequest($request);

        if (!$this->checkUserPassword($user, $form->get('current_password')->getData())) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.password.wrong'), Response::HTTP_BAD_REQUEST);
        }

        if ($form->isValid()) {
            $user->setPlainPassword($form->get('new')->getData());
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);

            $userManager->updateUser($user);

            return $this->serializeResponse($user);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }
}