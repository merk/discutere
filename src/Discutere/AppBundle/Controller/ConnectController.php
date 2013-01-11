<?php

namespace Discutere\AppBundle\Controller;

use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends BaseConnectController
{
    public function connectAction(Request $request)
    {
        $connect = $this->container->getParameter('hwi_oauth.connect');
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');

        $error = $this->getErrorForRequest($request);

        // if connecting is enabled and there is no user, redirect to the registration form
        if ($connect
            && !$hasUser
            && $error instanceof AccountNotLinkedException
        ) {
            $key = time();
            $session = $request->getSession();
            $session->set('_hwi_oauth.registration_error.'.$key, $error);

            if ($session->has('_comment_server.referer')) {
                $url = $session->get('_comment_server.referer');
                $session->remove('_comment_server.referer');

                return new RedirectResponse($url, 302);
            }

            return new RedirectResponse($this->generate('hwi_oauth_connect_registration', array('key' => $key)));
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }

        return $this->container->get('templating')->renderResponse('HWIOAuthBundle:Connect:login.html.twig', array(
            'error'           => $error,
            'resource_owners' => $this->getResourceOwners($request, $connect && $hasUser),
        ));
    }

    public function redirectAction(Request $request, $owner)
    {
        $this->container->get('session')->set('_comment_server.referer', $request->server->get('HTTP_REFERER'));
        $connect = $this->container->getParameter('hwi_oauth.connect');
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');

        $resourceOwner = $this->getResourceOwnerByName($owner);

        $ownerMap = $this->container->get('hwi_oauth.resource_ownermap.'.$this->container->getParameter('hwi_oauth.firewall_name'));
        $checkPath = $ownerMap->getResourceOwners()[$owner];

        $returnUrl = $connect & $hasUser ?
            $this->generate('hwi_oauth_connect_service', array('service' => $owner), true) :
            $this->getUriForCheckPath($request, $checkPath);

        $url = $resourceOwner->getAuthorizationUrl($returnUrl);

        return new RedirectResponse($url, 302);
    }

    protected function getResourceOwners(Request $request, $connect)
    {
        $ownerMap = $this->container->get('hwi_oauth.resource_ownermap.'.$this->container->getParameter('hwi_oauth.firewall_name'));

        $resourceOwners = array();
        foreach ($ownerMap->getResourceOwners() as $name => $checkPath) {
            $resourceOwners[$name] = array(
                'url' => $this->generate('app_login_redirect', array('owner' => $name), true),
                'name' => $name,
            );
        }

        return $resourceOwners;
    }
}