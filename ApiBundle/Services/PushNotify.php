<?php

namespace Geoks\ApiBundle\Services;

use Geoks\ApiBundle\Entity\Notification;
use Geoks\UserBundle\Entity\User;
use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\DataCollectorTranslator;

class PushNotify
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var DataCollectorTranslator
     */
    private $translator;

    /**
     * PushNotify constructor.
     *
     * @param Container $container
     * @param string $apiKey
     * @param DataCollectorTranslator $translator
     */
    public function __construct($apiKey, $translator, $container)
    {
        $this->container = $container;
        $this->apiKey = $apiKey;
        $this->translator = $translator;
    }

    /**
     * @param string $type
     * @param array $data
     * @param array $arrayMessage
     * @param User $receiver
     * @param User|null $sender
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($type, $data, $arrayMessage, $receiver, $sender = null)
    {
        $em = $this->container->get('doctrine')->getManager();

        $client = new Client();
        $client->setApiKey($this->apiKey);
        $client->injectHttpClient(new \GuzzleHttp\Client());

        $note = new Notification();
        $note->setTitle($arrayMessage['title']);
        $note->setBody($arrayMessage['body']);
        $note->setType($type);
        $note->setReceiver($receiver);
        $note->setCreatedAt(new \DateTime());

        if ($sender) {
            $note->setSender($sender);
        }

        $em->persist($note);
        $em->flush();

        $message = new Message();
        $notification = new \paragraph1\phpFCM\Notification($arrayMessage['title'], $arrayMessage['body']);
        $notification->setClickAction("FCM_PLUGIN_ACTIVITY");

        $message->addRecipient(new Device($receiver->getGcmToken()));
        $message->setNotification($notification)->setData($data);

        return $client->send($message);
    }
}