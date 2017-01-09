<?php

namespace Geoks\AdminBundle\Services;

use Symfony\Component\HttpFoundation\Session\Session;

class FlashBagHandler
{
    /**
     * @var Session $session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param bool $condition
     * @param string $arg
     * @return mixed
     */
    public function setFormFlashBag($condition, $arg)
    {
        $condition === true ? $test = 'success' : $test = 'fail';

        $this->session->getFlashBag()->add(
            'form',
            'form.' . $test . '.' . $arg
        );
    }
}
