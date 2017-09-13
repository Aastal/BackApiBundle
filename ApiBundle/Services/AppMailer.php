<?php

namespace Geoks\ApiBundle\Services;

use Geoks\UserBundle\Entity\User;
use Slot\MandrillBundle\Message;
use Slot\MandrillBundle\Dispatcher;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;

class AppMailer
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var array
     */
    private $config;

    /**
     * @var DataCollectorTranslator
     */
    private $translator;

    /**
     * AppMailer constructor.
     *
     * @param ContainerInterface $container
     * @param $translator
     * @param Dispatcher $dispatcher
     * @param $router
     */
    public function __construct(ContainerInterface $container, $translator, Dispatcher $dispatcher, Router $router)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
        $this->router = $router;
        $this->projectName = $this->container->getParameter('geoks_admin.app_name');

        $this->setConfig();
    }

    private function setConfig()
    {
        $senderName = $this->container->getParameter('slot_mandrill.default.sender_name');
        $sender = $this->container->getParameter('slot_mandrill.default.sender');
        $lc = strtolower($this->projectName);

        $this->config = array(
            'forgotten_password' => array(
                'to' => 'USEREMAIL',
                'fromEmail' => $sender,
                'fromName' => $senderName,
                'subject' => $lc . '.user.password.recovery'
            ),
            'forgotten_password_link' => array(
                'to' => 'USEREMAIL',
                'fromEmail' => $sender,
                'fromName' => $senderName,
                'subject' => $lc . '.user.password.recovery'
            ),
            'login_lock' => array(
                'to' => 'USEREMAIL',
                'fromEmail' => $sender,
                'fromName' => $senderName,
                'subject' => $lc . '.user.lock'
            ),
            'email_check' => array(
                'to' => 'USEREMAIL',
                'fromEmail' => $sender,
                'fromName' => $senderName,
                'subject' => $lc . '.user.email_check'
            )
        );

        if ($this->container->has('app.mailer')) {
            $this->config += $this->container->get('app.mailer')->getConfig($sender, $senderName, $lc);
        }
    }

    /**
     * @param string $model
     * @param null $entity
     * @param string $locale
     * @return bool
     */
    public function send($model, $entity = null, $locale = 'fr')
    {
        $message = new Message();

        $this->translator->setLocale($locale);

        $message
            ->setFromEmail($this->config[$model]['fromEmail'])
            ->setFromName($this->config[$model]['fromName'])
            ->setSubject($this->translator->trans($this->config[$model]['subject']))
        ;

        $message = $this->__mergeVars($message, $entity, $model);

        $this->dispatcher->send($message, $model . '_' . $locale);

        return true;
    }

    /**
     * @param $message
     * @param $entity
     * @return mixed
     */
    private function __mergeVars($message, $entity, $model)
    {
        $data = $this->__normalizer($entity);

        foreach($data as $key => $value){
            $message->addGlobalMergeVar($key, $value);
        }

        $message->addTo($data[$this->config[$model]['to']]);

        return $message;
    }

    /**
     * @param $entity
     * @return array
     */
    private function __normalizer($entity)
    {
        $name = (new \ReflectionClass($entity))->getShortName();

        if ($this->container->has('app.mailer')) {
            $normalizer = $this->container->get('app.mailer')->getNormalizer($entity, $name);

            if ($normalizer !== null) {
                return $normalizer;
            }
        }

        switch($name)
        {
            default:

                /** @var User $entity */
                if ($this->container->hasParameter('base_url')) {

                    return [
                        "USEREMAIL" => $entity->getEmail(),
                        "FIRSTNAME" => $entity->getFirstname(),
                        "LASTNAME"  => $entity->getLastname(),
                        "TOKEN"     => $entity->getConfirmationToken(),
                        "LINK"      => $this->container->getParameter('base_url') . '/change-password?token=' . $entity->getConfirmationToken()
                    ];
                } else {
                    return [
                        "USEREMAIL" => $entity->getEmail(),
                        "FIRSTNAME" => $entity->getFirstname(),
                        "LASTNAME"  => $entity->getLastname(),
                        "TOKEN"     => $entity->getConfirmationToken()
                    ];
                }

                break;
        }
    }
}
