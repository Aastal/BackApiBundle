<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Entity\AccessToken;
use Geoks\ApiBundle\Form\Basic\CreateForm;
use Geoks\ApiBundle\Form\Security\ResetPasswordForm;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $userRepository = "AppBundle\\Entity\\User";

    /**
     * @var string
     */
    protected $formCreate = "Geoks\\ApiBundle\\Form\\Basic\\CreateForm";

    /**
     * @var string
     */
    protected $adminViews = "GeoksAdminBundle";

    /**
     * @return string
     */
    protected function getClassName()
    {
        return (new \ReflectionClass($this->userRepository))->getShortName();
    }

    /**
     * @return string
     */
    protected function getUserRepository()
    {
        if ($this->userRepository) {
            return $this->userRepository;
        }

        return $this->getParameter('geoks_api.user_class');
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
    protected function getAdminViews()
    {
        return $this->adminViews;
    }

    /**
     * @param null|string $userRepository
     * @param null|string $formCreate
     */
    public function __construct($userRepository = null, $formCreate = null)
    {
        $this->userRepository = $userRepository;

        if ($formCreate) {
            $this->formCreate = $formCreate;
        }
    }

    public function loginAction(Request $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');

        if ($request->getMethod() == "GET") {
            return $this->serializeResponse('geoks.user.access_token', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if ($email !== null && $password !== null) {
            $em = $this->getDoctrine()->getManager();

            /** @var User $user */
            $user = $em->getRepository($this->getUserRepository())->findOneByEmail($email);

            if (!$user) {
                return $this->serializeResponse('geoks.user.login.wrong', Response::HTTP_FORBIDDEN);
            }

            if ($this->checkUserPassword($user, $password)) {
                if ($user->isEnabled()) {

                    $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername());

                    return $this->serializeResponse([
                        "details" => $user,
                        "access_token" => $this->get('geoks.user_provider')->getAccessToken()
                    ]);
                } else {
                    return $this->serializeResponse('geoks.user.disabled', Response::HTTP_FORBIDDEN);
                }
            } else {
                return $this->serializeResponse('geoks.user.login.wrong', Response::HTTP_FORBIDDEN);
            }
        }


        return $this->serializeResponse('geoks.missing_param', Response::HTTP_BAD_REQUEST);
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
            ]]
        );
    }

    public function loginFacebookAction(Request $request)
    {
        $facebookToken = $request->get('facebook_token');

        if ($facebookToken) {
            $user = $this->get('geoks.user_provider')->loadByFacebookToken($facebookToken);

            if (!$user) {
                return $this->serializeResponse([
                    "success" => false
                ]);
            }

            return $this->serializeResponse([
                "details" => $user,
                "access_token" => $this->get('geoks.user_provider')->getAccessToken()
            ]);
        }

        return $this->serializeResponse('geoks.user.notFound', Response::HTTP_NOT_FOUND);
    }

    public function logoutAction()
    {
        if (!$this->getUser()) {
            return $this->serializeResponse('geoks.user.notFound', Response::HTTP_NOT_FOUND);
        }

        $em = $this->getDoctrine()->getManager();

        $this->getUser()->setGcmToken(null);
        $em->flush();

        return $this->serializeResponse('geoks.user.logout');
    }

    public function forgottenPasswordAction($email)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository($this->getUserRepository())->findOneByEmail($email);

        if ($user) {
            $token = $this->container->getParameter('fos_user.resetting.token_ttl');

            if ($user->isPasswordRequestNonExpired($token)) {
                return $this->serializeResponse('geoks.user.email.nonExpired', Response::HTTP_ALREADY_REPORTED);
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken(rand(100000, 999999));
            }

            $user->setPasswordRequestedAt(new \DateTime());
            $this->container->get('fos_user.user_manager.default')->updateUser($user);

            $mailer = $this->get('geoks.api.mailer');
            $mailer->send('forgotten_password', $user);

            return $this->serializeResponse([
                'reset' => true,
                'time' => $user->getPasswordRequestedAt()->format('Y-m-d H:i:s')
            ]);
        }

        return $this->serializeResponse('geoks.user.email.invalid', Response::HTTP_BAD_REQUEST);
    }

    public function setNewPasswordAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository($this->getUserRepository())->findOneByConfirmationToken($token);

        if (!$user) {
            return $this->serializeResponse('geoks.user.token.notFound', Response::HTTP_NOT_FOUND);
        }

        if ($token == $user->getConfirmationToken()) {
            if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                return $this->serializeResponse('geoks.user.password.expired', Response::HTTP_BAD_REQUEST);
            }

            $form = $this->createForm(ResetPasswordForm::class);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $user->setPlainPassword($form->get('new')->getData());
                $user->setConfirmationToken(null);
                $user->setPasswordRequestedAt(null);
                $user->setEnabled(true);

                $em->flush();

                return $this->serializeResponse(["details" => $user]);
            }

            return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
        }

        return $this->serializeResponse('geoks.user.password.badToken', Response::HTTP_BAD_REQUEST);
    }

    public function subscribeAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        /** @var User $user */
        $user = $userManager->createUser();

        if ($this->getFormCreate() == CreateForm::class) {
            $form = $this->createForm($this->getFormCreate(), $user, [
                'method' => 'POST',
                'data_class' => $this->getUserRepository(),
                'service_container' => $this->get('service_container'),
                'fields' => array_keys($request->request->all())
            ]);
        } else {
            $form = $this->createForm($this->getFormCreate(), $user, [
                'method' => 'POST'
            ]);
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $user->addRole('ROLE_USER');
            $user->setEnabled(true);

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPassword());

            $user->setPassword($encoded);

            $em->persist($user);
            $em->flush();

            $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername());

            return $this->serializeResponse([
                "details" => $user,
                "access_token" => $this->get('geoks.user_provider')->getAccessToken()
            ]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function subscribeCustomAction(Request $request)
    {
        $class = $this->getUserRepository();

        /** @var User $user */
        $user = new $class();

        if ($this->getFormCreate() == CreateForm::class) {
            $form = $this->createForm($this->getFormCreate(), $user, [
                'method' => 'POST',
                'data_class' => $this->getUserRepository(),
                'service_container' => $this->get('service_container'),
                'fields' => array_keys($request->request->all())
            ]);
        } else {
            $form = $this->createForm($this->getFormCreate(), $user, [
                'method' => 'POST'
            ]);
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $user->addRole('ROLE_USER');
            $user->setEnabled(true);

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPassword());

            $user->setPassword($encoded);

            $em->persist($user);
            $em->flush();

            $this->get('geoks.user_provider')->loadUserByUsername($user->getUsername());

            return $this->serializeResponse([
                "details" => $user,
                "access_token" => $this->get('geoks.user_provider')->getAccessToken()
            ]);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }

    public function verifyEmailAction($email)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getUserRepository())->findOneByEmail($email);

        return $this->serializeResponse(['already-subscribed' => ($user) ? true : false]);
    }

    public function verifyUsernameAction($username)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getUserRepository())->findOneByUsername($username);

        return $this->serializeResponse(['already-subscribed' => ($user) ? true : false]);
    }
}
