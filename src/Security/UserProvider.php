<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Exception\JwtManagerException;
use App\Service\BillingClient;
use App\Service\JwtTokenManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{

    public function __construct(
        private BillingClient $billingClient,
        private JwtTokenManager $jwtManager,
    ) {
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        try {
            $responseBilling = $this->billingClient->userCurrent($identifier);
            if (isset($responseBilling['username'])) {
                $user = new User();
                $user->setEmail($responseBilling['username'])
                    ->setApiToken($identifier)
                    ->setRoles($responseBilling['roles']);
                return $user;
            }
            throw new UserNotFoundException();
        } catch (BillingUnavailableException $e) {
            throw new UserNotFoundException();
        }
    }

    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }
        try {
            if ($this->jwtManager->isExpired($user->getApiToken())) {
                try {
                    $newToken  = $this->billingClient->refreshToken($user->getRefreshToken())['token'];
                    $user->setApiToken($newToken);
                } catch (BillingUnavailableException $ex) {
                    throw new UserNotFoundException();
                }
            }
        } catch(JwtManagerException $e) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // TODO: when hashed passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newHashedPassword);
    }
}
