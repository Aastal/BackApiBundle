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
     * @Route("/login", name="geoks_admin_login")
     *
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function loginAction()
    {
        return parent::loginAction();
    }

    /**
     * @Route("/login/check", name="geoks_admin_login_check")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function loginCheckAction(Request $request)
    {
        return parent::loginCheckAction($request);
    }
}
