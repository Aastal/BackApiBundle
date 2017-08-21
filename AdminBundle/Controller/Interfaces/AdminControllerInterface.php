<?php

namespace Geoks\AdminBundle\Controller\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AdminControllerInterface
{
    /**
     * Main route of the back-office.
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request);
}
