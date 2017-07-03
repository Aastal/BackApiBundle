<?php

namespace Geoks\AdminBundle\Controller\ApiDocs;

use Geoks\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class AdminDoc extends AdminController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        return parent::loginAction();
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginCheckAction(Request $request)
    {
        return parent::loginCheckAction($request);
    }
}
