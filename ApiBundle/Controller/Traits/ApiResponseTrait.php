<?php

namespace Geoks\ApiBundle\Controller\Traits;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Form;
use Geoks\ApiBundle\Services\Serializer;

trait ApiResponseTrait
{
    /**
     * Serialize and return JsonResponse of params.
     * If the first param is \Traversable (a Class like User), you should define the context group in the key.
     *
     * @param string|array|Form $data
     * @param integer $status
     * @return JsonResponse
     */
    protected function serializeResponse($data, $status = 200)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('geoks.api.serializer');

        // Success
        if ($status == Response::HTTP_OK) {
            return new JsonResponse($serializer->serializeData($data), $status);
        }

        // Parse Error
        if ($data instanceof Form) {
            $results = ['error' => $this->formErrorsToArray($data)];
        } elseif (is_array($data)) {
            $results = [];

            foreach ($data as $key => $value) {
                $results += [$key => $value];
            }
        } else {
            $results = ['error' => $data];
        }

        return new JsonResponse($results, $status);
    }

    /**
     * Serialize and return JsonResponse of params but don't rename the json key.
     * If the first param is \Traversable (a Class like User), you should define the context group in the key.
     *
     * @param string|array|Form $data
     * @param integer $status
     * @return JsonResponse
     */
    protected function simpleSerializeResponse($data, $status = 200)
    {
        // Success
        if ($status == Response::HTTP_OK) {
            return new JsonResponse($this->get('geoks.api.serializer')->simpleSerializeData($data), $status);
        }

        // Parse Error
        if ($data instanceof Form) {
            $results = ['error' => $this->formErrorsToArray($data)];
        } elseif (is_array($data)) {
            $results = [];

            foreach ($data as $key => $value) {
                $results += [$key => $value];
            }
        } else {
            $results = ['error' => $data];
        }

        return new JsonResponse($results, $status);
    }

    /**
     * Get form errors in key value array
     *
     * @param \Symfony\Component\Form\Form $form
     * @param boolean $first
     * @return array
     */
    protected function formErrorsToArray($form, $first = true)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            if ($first) {
                $errors[$error->getOrigin()->getName()][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $key => $child) {
            if ($err = $this->formErrorsToArray($child, false)) {
                $errors[$key] = $err;
            }
        }

        return $errors;
    }

    /**
     *
     * @param $user
     * @param string $password
     * @return boolean
     */
    protected function checkUserPassword($user, $password)
    {
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);

        if (!$encoder) {
            return false;
        }

        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    /**
     * @param $user
     * @param $raw
     * @return mixed
     */
    protected function encodeUserPassword($user, $raw)
    {
        $encoder = $this->get('security.password_encoder');

        return $encoder->encodePassword($user, $raw);
    }
}
