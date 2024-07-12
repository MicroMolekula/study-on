<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;


class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $password = $request->getPayload()->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new SelfValidatingPassport(
            new UserBadge(implode(' ',[$email, $password]), function(string $credentials): ?UserInterface {
                $credentials = explode(' ', $credentials);
                $username = $credentials[0];
                $password = $credentials[1];
                try {
                    $responseBilling = $this->billingClient->auth([
                        'username' => $username,
                        'password' => $password,
                    ]);
                } catch (BillingUnavailableException $exception) {
                    throw new CustomUserMessageAuthenticationException('Сервис времменно не доступен. Попробуйте авторизоваться позднее.');
                }
                $user = new User();
                if (isset($responseBilling['token'])) {
                    $userData = $this->billingClient->userCurrent($responseBilling['token']);
                    $user->setEmail($userData['username'])
                        ->setApiToken($responseBilling['token'])
                        ->setRoles($userData['roles']);
                } else {
                    throw new CustomUserMessageAuthenticationException($responseBilling['message']);
                }
                return $user;
            }),
            [
                new RememberMeBadge(),
            ]      
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
