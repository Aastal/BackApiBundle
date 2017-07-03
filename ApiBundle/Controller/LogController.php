<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Entity\Log;
use Geoks\ApiBundle\Form\Log\CreateForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogController extends ApiController
{
    public function sendErrorAction(Request $request)
    {
        if (!$this->getUser()) {
            return $this->serializeResponse(["error" => $this->get('translator')->trans("geoks.user.notConnected")], Response::HTTP_FORBIDDEN);
        }

        $log = new Log();
        $log->setType("front");

        $form = $this->createForm(CreateForm::class, $log, [
            'method' => 'POST'
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $logger = $this->get('logger');
            $logger->error($log->getDescription(), $log->getContext());

            $em = $this->container->get('doctrine.orm.entity_manager');

            $em->persist($log);
            $em->flush();

            return $this->serializeResponse($log);
        }

        return $this->serializeResponse($form, Response::HTTP_BAD_REQUEST);
    }
}