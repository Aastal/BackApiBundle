<?php

namespace Geoks\ApiBundle\Controller\ApiDocs;

use Geoks\ApiBundle\Controller\LogController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Geoks\AdminBundle\Controller\AdminPanelController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/logger")
 */
class LogDoc extends LogController
{
    /**
     * @ApiDoc(
     *  section="Logs",
     *  description="Send an error",
     *  parameters={
     *     {"name"="description", "dataType"="string", "required"=true, "description"="description of the error"},
     *     {"name"="page", "dataType"="string", "required"=false, "description"="page where the error appear"},
     *     {"name"="details", "dataType"="string", "required"=false, "description"="details"},
     * },
     *  statusCodes = {
     *      200 = "Returned when successful",
     *      403 = "geoks.user.notConnected",
     *  },
     * )
     * @Route("/send-error", name="logs_create_errors")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendErrorAction(Request $request)
    {
        return parent::sendErrorAction($request);
    }
}
