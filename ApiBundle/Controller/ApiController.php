<?php

namespace Geoks\ApiBundle\Controller;

use Geoks\ApiBundle\Controller\Interfaces\ApiControllerInterface;
use Geoks\ApiBundle\Controller\Traits\ApiResponseTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class ApiController extends Controller implements ApiControllerInterface
{
    use ApiResponseTrait;
}
