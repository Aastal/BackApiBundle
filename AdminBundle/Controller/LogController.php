<?php

namespace Geoks\AdminBundle\Controller;

use Geoks\AdminBundle\Controller\AdminController as GeoksAdminBundle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class LogController extends GeoksAdminBundle
{
    public function __construct()
    {
        $entityRepository = "Geoks\\ApiBundle\\Entity\\Log";

        $fieldsExport = [
            "id", "created", "description", "context"
        ];

        parent::__construct($entityRepository, null, null,null, $fieldsExport);
    }
}
