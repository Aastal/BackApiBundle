<?php

namespace Geoks\AdminBundle\Controller\ApiDocs;

use Geoks\AdminBundle\Controller\LogController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route("/logs")
 */
class LogDoc extends LogController
{
    /**
     * @Route("/index", name="geoks_admin_logs_index")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * @Route("/{id}/show", name="geoks_admin_logs_show")
     * @Method({"GET"})
     *
     * @param integer $id
     * @return Response
     */
    public function showAction($id)
    {
        return parent::showAction($id);
    }

    /**
     * @Route("/create", name="geoks_admin_logs_create")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * @Route("/{id}/update", name="geoks_admin_logs_update", options={"expose"=true})
     * @Method({"GET", "PATCH", "PUT"})
     *
     * @param Request $request
     * @param integer $id
     * @return Response
     */
    public function updateAction(Request $request, $id)
    {
        return parent::updateAction($request, $id);
    }

    /**
     * @Route("/{id}/delete", name="geoks_admin_logs_delete")
     * @Method({"GET"})
     *
     * @param integer $id
     * @return Response
     */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    /**
     * @Route("/import", name="geoks_admin_logs_import", options={"expose"=true})
     *
     * @param Request $request
     * @return Response
     */
    public function importAction(Request $request)
    {
        return parent::importAction($request);
    }

    /** AJAX */

    /**
     * @Route("/search", name="geoks_admin_logs_search", options={"expose"=true})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        return parent::searchAction($request);
    }

    /**
     * @Route("/entities-remove", name="delete_logs_entities", options={"expose"=true})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function multipleDeleteAction(Request $request)
    {
        return parent::multipleDeleteAction($request);
    }

    /**
     * @Route("/entities-export", name="export_logs_entities", options={"expose"=true})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dataExportAction(Request $request)
    {
        return parent::dataExportAction($request);
    }
}
