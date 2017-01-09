<?php

namespace ApiBundle\Controller;

use Geoks\ApiBundle\Controller\ApiController;
use Geoks\UserBundle\Entity\User;
use Geoks\UserBundle\Entity\Message;
use Geoks\UserBundle\Entity\Thread;
use Geoks\UserBundle\Entity\ThreadMetadata;
use FOS\MessageBundle\FormModel\NewThreadMessage;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class MessagingController extends ApiController
{
    public function getUnreadMessage()
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user) {
            $em = $this->getDoctrine()->getManager();
            $message = $em->getRepository('GeoksUserBundle:Message')->findUnreadMessage($user);

            if (count($message) > 0) {
                return $this->serializeResponse([
                    'unread' => true
                ]);
            }

            return $this->serializeResponse([
                'unread' => false
            ]);
        }

        return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_NOT_FOUND);
    }

    public function listAction(Request $request)
    {
        $provider = $this->get('fos_message.provider');
        $serializer = $this->get('jms_serializer');

        $page   = $request->get('page', 1);
        $offset = $request->get('offset', 20);

        $receivedThreads = $serializer->toArray($provider->getInboxThreads(), SerializationContext::create()->setGroups(['thread']));
        $sentThreads = $serializer->toArray($provider->getSentThreads(), SerializationContext::create()->setGroups(['thread']));

        $threads = array_unique(array_merge($receivedThreads, $sentThreads), SORT_REGULAR);

        $threads = array_slice($threads, ($page - 1) * $offset, $offset);
        sort($threads);

        usort($threads, function($a, $b) {
            $dateA = new \DateTime($a['last_message']['created_at']);
            $dateB = new \DateTime($b['last_message']['created_at']);

            if ($dateA->format('U') < $dateB->format('U')) {
                return 1;
            }

            if ($dateA->format('U') > $dateB->format('U')) {
                return -1;
            }

            if ($dateA->format('U') == $dateB->format('U')) {
                return 0;
            }
        });

        return $this->serializeResponse(["threads" => $threads]);
    }

    public function getThreadAction($id)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_NOT_FOUND);
        }

        $userManager = $this->get('fos_user.user_manager');

        /** @var User $recipient */
        $recipient = $userManager->find($id);

        if (!$recipient) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_NOT_FOUND);
        }

        $em = $this->getDoctrine()->getManager();
        $thread = $em->getRepository('AppBundle:Thread')->findExistingThreadBetween($user, $recipient);

        if (!$thread) {
            $thread = new Thread();
            $thread->setCreatedBy($user);
            $thread->setCreatedAt(new \DateTime('now'));
            $thread->setSubject("messaging between " . $this->getUser()->getEmail() . " and " . $recipient->getEmail());
            $thread->addParticipant($user);
            $thread->addParticipant($recipient);

            $threadMeta1 = new ThreadMetadata();
            $threadMeta1->setParticipant($user);
            $threadMeta1->setThread($thread);

            $threadMeta2 = new ThreadMetadata();
            $threadMeta2->setParticipant($recipient);
            $threadMeta2->setThread($thread);

            $em->persist($thread);
            $em->persist($threadMeta1);
            $em->persist($threadMeta2);
            $em->flush();
        }

        return $this->serializeResponse(['thread' => $thread]);
    }

    public function getAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $page   = $request->get('page', 1) - 1;
        $offset = $request->get('offset', 30);

        /** @var Thread $thread */
        $thread = $this->get('fos_message.thread_manager')->findThreadById($id);

        if (!$thread || !$this->getUser()) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_NOT_FOUND);
        }

        if (!$thread->isParticipant($this->getUser())) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.thread.forbidden'), Response::HTTP_FORBIDDEN);
        }

        $messages = $thread->paginateMessages($page, $offset);

        foreach ($messages as $message) {

            /** @var Message $message */
            if ($message->getRecipient() == $this->getUser()) {
                $message->setIsReadByParticipant($message->getRecipient(), true);
            }
        }

        $em->flush();
        $em->clear();

        $thread->setMessages($messages);

        $thread = $this->get('serializer')->toArray(
            $thread,
            SerializationContext::create()->setGroups(['messaging'])
        );

        return $this->serializeResponse(["thread" => $thread]);
    }

    public function sendAction(Request $request, $id)
    {
        $userManager = $this->get('fos_user.user_manager');

        /** @var User $recipient */
        $recipient = $userManager->find($id);

        if (!$recipient || !$this->getUser()) {
            return $this->serializeResponse($this->get('translator')->trans('geoks.user.notFound'), Response::HTTP_BAD_REQUEST);
        }

        if ($recipient == $this->getUser()) {
            return $this->serializeResponse([$this->get('translator')->trans('geoks.message.send.same')], Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $existingThread = $em->getRepository('AppBundle:Thread')->findExistingThreadBetween($this->getUser(), $recipient);

        if (null !== $existingThread) {
            $message = $this->__replyTo($request, $existingThread, $recipient);
        } else {
            $message = $this->__sendTo($request, $recipient);
        }

        if (!$message) {
            return $this->serializeResponse([$this->get('translator')->trans('geoks.message.send.unable')], Response::HTTP_BAD_REQUEST);
        } else {
            $sender = $this->get('fos_message.sender');
            $sender->send($message);

            return $this->getAction($request, $message->getThread()->getId());
        }
    }

    private function __sendTo($request, $recipient){
        $message = new NewThreadMessage();
        $message->setRecipient($recipient);
        $message->setSubject("messaging between " . $this->getUser()->getEmail() . " and " . $recipient->getEmail());

        $form = $this->createForm(\ApiBundle\Form\Message\CreateForm::class, $message);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $composer = $this->get('fos_message.composer');

            $newMessage = $composer->newThread()
                ->setSubject($message->getSubject())
                ->addRecipient($message->getRecipient())
                ->setSender($this->getUser())
                ->setBody($message->getBody())
                ->getMessage()
            ;

            return $newMessage;
        }

        return false;
    }

    private function __replyTo($request, $thread, $recipient){
        $message = new NewThreadMessage();
        $message->setRecipient($recipient);
        $message->setSubject("messaging between " . $this->getUser()->getEmail() . " and " . $recipient->getEmail());

        $form = $this->createForm(\ApiBundle\Form\Message\CreateForm::class, $message);
        $form->handleRequest($request);

        if($form->isValid()){
            $composer = $this->get('fos_message.composer');

            $newMessage = $composer->reply($thread)
                ->setSender($this->getUser())
                ->setBody($message->getBody())
                ->getMessage()
            ;

            return $newMessage;
        }

        return false;
    }
}
