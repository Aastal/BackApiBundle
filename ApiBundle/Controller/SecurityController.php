<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Entity\AccessToken;
use Geoks\ApiBundle\Form\Security\ResetPasswordForm;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Geoks\ApiBundle\Services\RestRequest;
use Geoks\ApiBundle\Controller\ApiController;

/**
 * Class SecurityController
 * @package Geoks\ApiBundle\Controller
 *
 * Default login, loginFacebook and other security components
 */
abstract class SecurityController extends ApiController
{
    protected $className = 'User';
    protected $userRepository = '';

    protected $formCreate = "Geoks\\ApiBundle\\Form\\Basic\\CreateForm";
    protected $adminViews = "GeoksAdminBundle";

    public function loginFormAction()
    {
        return $this->render($this->adminViews . ':Security:login.html.twig');
    }

    public function loginAction(Request $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');

        if ($email !== null && $password !== null) {
            $em = $this->getDoctrine()->getManager();

            /** @var User $user */
            $user = $em->getRepository($this->userRepository)->findOneByEmail($email);

            if (!$user) {
                return $this->serializeResponse($this->get('translator')->trans('geoks.user.email.invalid'), Response::HTTP_NOT_FOUND);
            }

            if ($this->checkUserPassword($user, $password)) {
                if ($user->isEnabled()) {

                    $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername());

                    return $this->serializeResponse([
                        "user" => $user,
                        "accessToken" => $this->get('geoks.user_provider')->getAccessToken()
                    ]);
                } else {
                    return $this->serializeResponse($this->get('translator')->trans('geoks.user.disabled'), Response::HTTP_FORBIDDEN);
                }
            } else {
                return $this->serializeResponse($this->get('translator')->trans('geoks.user.login.wrong'), Response::HTTP_FORBIDDEN);
            }
        }

        return $this->serializeResponse($this->get('translator')->trans('geoks.missing_param'), Response::HTTP_BAD_REQUEST);
    }

    public function loginOptionsAction()
    {
        return $this->serializeResponse(
            ["POST" => [
                "description" => "test",
                "parameters" => [
                    "email" => [
                        "type" => "string",
                        "description" => "User's email",
                        "required" => true
                    ],
                    "password" => [
                        "type" => "string",
                        "description" => "User's password",
                        "required" => true
                    ]
                ],
                "example" => [
                    "email" => "admin@gmail.fr",
                    "password" => "testLoginPassword"
                ]
            ]]);
    }

    public function loginFacebookAction(Request $request)
    {
        $facebookToken = $request->get('facebook_token');

        if ($facebookToken) {
            $user = $this->get('geoks.user_provider')->loadByFacebookToken($facebookToken);

            if (!$user) {
                return $this->serializeResponse([
                    "user" => false
                ]);
            }

            return $this->serializeResponse([
                "user" => $user,
                "accessToken" => $this->get('geoks.user_provider')->getAccessToken()
            ]);
        }

        return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_NOT_FOUND);
    }

    public function forgottenPasswordAction($email)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository($this->userRepository)->findOneByEmail($email);

        if ($user) {
            $token = $this->container->getParameter('fos_user.resetting.token_ttl');

            if ($user->isPasswordRequestNonExpired($token)) {
                return $this->serializeResponse($this->get('translator')->trans('geoks.user.email.nonExpired'), Response::HTTP_ALREADY_REPORTED);
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken(rand(100000, 999999));
            }

            $user->setPasswordRequestedAt(new \DateTime());
            $this->container->get('fos_user.user_manager')->updateUser($user);

            $mailer = $this->get('geoks.api.mailer');
            $mailer->send('forgotten_password', $user);

            return $this->serializeResponse([
                'reset' => true,
                'time' => $user->getPasswordRequestedAt()->format('Y-m-d H:i:s')
            ]);
        }

        return $this->serializeResponse($this->get('translator')->trans('geoks.user.email.invalid'), Response::HTTP_BAD_REQUEST);
    }

    public function setNewPasswordAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository($this->userRepository)->findOneByConfirmationToken($token);

        if (!$user) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.token.notFound'), Response::HTTP_NOT_FOUND);
        }

        if ($token == $user->getConfirmationToken()) {
            if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                return $this->serializeResponse($this->get('translator')->trans('geoks.user.password.expired'), Response::HTTP_BAD_REQUEST);
            }

            $form = $this->createForm(ResetPasswordForm::class);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user->setPlainPassword($form->get('new')->getData());
                $user->setConfirmationToken(null);
                $user->setPasswordRequestedAt(null);
                $user->setEnabled(true);

                $em->flush();

                return $this->serializeResponse($user);
            }

            return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
        }

        return $this->serializeResponse($this->get('translator')->trans('geoks.user.password.badToken'), Response::HTTP_BAD_REQUEST);
    }

    public function subscribeAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $formUser = new $this->formCreate($this->container, $this->userRepository);
        $form = $this->createForm($formUser, $user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->addRole('ROLE_USER');
            $user->setEnabled(1);
            $user->setPlainPassword($user->getPassword());

            if (!$user->getUsername()) {
                $user->setUsername($user->getEmail().$user->getFirstname().$user->getLastname());
            }

            $userManager->updateUser($user);

            $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername());

            return $this->serializeResponse([
                "user" => $user,
                "accessToken" => $this->get('geoks.user_provider')->getAccessToken()
            ]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }
}
