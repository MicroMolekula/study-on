<?php

namespace App\Controller;

use App\Dto\UserRegisterDto;
use App\Exception\BillingUnavailableException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Service\BillingClient;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\User;

class RegisterController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
        private UserAuthenticatorInterface $userAuthenticator,
        private BillingAuthenticator $billingAuthenticator,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        } 

        $user = new UserRegisterDto();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);
        $error = null;

        if($form->isSubmitted() && $form->isValid()) {
            try {
                $responseBilling = $this->billingClient->register([
                    'username' => $user->email,
                    'password' => $user->password,
                ]);
                
                if(isset($responseBilling['token'])) {
                    $userNew = new User();
                    $userNew->setEmail($user->email)
                        ->setRoles($responseBilling['roles'])
                        ->setApiToken($responseBilling['token']);
            
                    $this->userAuthenticator->authenticateUser(
                        $userNew, 
                        $this->billingAuthenticator, 
                        $request
                    );
                    return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
                } else if (isset($responseBilling['code']) && $responseBilling['code'] === 400) {
                    $error = $responseBilling['message'];
                }
            } catch (BillingUnavailableException $e) {
                $error = 'Сервис временно недоступен. Попробуйте зарегистироваться позже.';
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
            'user' => $user,
            'error' => $error,
        ]);
    }
}
