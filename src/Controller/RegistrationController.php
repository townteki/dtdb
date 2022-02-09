<?php

namespace App\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends BaseController
{
    /**
     * @Route("/register/confirm/{token}", name="fos_user_registration_confirm_override",  methods={"GET"})
     * @param Request $request
     * @param $token
     * @return RedirectResponse|Response
     */
    public function confirmAction(Request $request, $token)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            // User with token not found. Do whatever you want here
            return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
        }
        // Token found. Letting the FOSUserBundle's action handle the confirmation
        return parent::confirmAction($request, $token);
    }
}
