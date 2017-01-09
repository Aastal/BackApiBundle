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
     * @param array $bodyParam
     * @param User $receiver
     * @param User $sender
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($type, $data, $bodyParam, $receiver, $sender)
    {
        $em = $this->container->get('doctrine')->getManager();

        $client = new Client();
        $client->setApiKey($this->apiKey);
        $client->injectHttpClient(new \GuzzleHttp\Client());

        $note = new Notification($bodyParam['title'], $bodyParam['body']);
        $note->setType($type);
        $note->setReceiver($receiver);
        $note->setSender($sender);
        $note->setCreatedAt(new \DateTime());

        $em->persist($note);
        $em->flush();

        $message = new Message();
        $notification = new \paragraph1\phpFCM\Notification($bodyParam['title'], $bodyParam['body']);
        $message->addRecipient(new Device($receiver->getGcmToken()));
        $message->setNotification($notification)->setData($data);

        return $client->send($message);
    }
}