<?php

namespace Geoks\AdminBundle\Controller;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AdminPanelController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $meta = $this->getDoctrine()->getManager()->getMetadataFactory()->getAllMetadata();

        $reflections = null;

        foreach ($meta as $m) {
            /** @var ClassMetadata $m */
            $reflections[] = $m->getReflectionClass();
        }

        return $this->render("@GeoksAdmin/adminPanel/index.html.twig", [
            'reflections' => $reflections
        ]);
    }
}