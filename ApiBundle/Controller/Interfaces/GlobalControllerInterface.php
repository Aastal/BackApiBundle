<?php

namespace Geoks\ApiBundle\Controller\Interfaces;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface GlobalControllerInterface
{
    /**
     * Get All the entities.
     *
     * @return JsonResponse
     */
    public function getAllAction();

    /**
     * Get an entity by id.
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function getOneAction($id);

    /**
     * Create a entity.
     *
     * @param Request $request
     * @param array $customSetters
     * @return JsonResponse
     */
    public function createAction(Request $request, $customSetters = []);

    /**
     * Update a entity.
     *
     * @param Request $request
     * @param integer $id
     * @param array $customSetters
     * @return JsonResponse
     */
    public function updateAction(Request $request, $id, $customSetters = []);

    /**
     * Delete a entity.
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function deleteAction($id);
}