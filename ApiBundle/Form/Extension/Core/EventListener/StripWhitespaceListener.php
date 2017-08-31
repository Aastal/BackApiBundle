<?php

namespace Geoks\ApiBundle\Form\Extension\Core\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StripWhitespaceListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::SUBMIT => 'submit');
    }

    public function submit(FormEvent $event)
    {
        $data = $event->getData();

        if (is_string($data)) {
            $data = preg_replace("/\s/", "", $data);
            $event->setData($data);
        }
    }
}