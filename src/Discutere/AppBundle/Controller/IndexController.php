<?php

namespace Discutere\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends Controller
{
    public function indexAction()
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->container->get('session');
        if ($session->has('_comment_server.referer')) {
            $url = $session->get('_comment_server.referer');
            $session->remove('_comment_server.referer');

            return new RedirectResponse($url, 302);
        }

        return $this->render('DiscutereAppBundle:Index:index.html.twig');
    }
}