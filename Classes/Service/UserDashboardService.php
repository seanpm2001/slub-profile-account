<?php

declare(strict_types=1);

/*
 * This file is part of the package slub/slub-profile-account
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Slub\SlubProfileAccount\Service;

use Slub\SlubProfileAccount\Domain\Model\User\Dashboard as User;
use Slub\SlubProfileAccount\Domain\Repository\User\DashboardRepository as UserRepository;
use Slub\SlubProfileAccount\Sanitization\WidgetSanitization;
use Slub\SlubProfileAccount\Utility\FileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class UserDashboardService
{
    protected AccountService $accountService;
    protected PersistenceManager $persistenceManager;
    protected UserRepository $userRepository;
    protected WidgetSanitization $widgetSanitization;

    /**
     * @param AccountService $accountService
     * @param PersistenceManager $persistenceManager
     * @param UserRepository $userRepository
     * @param WidgetSanitization $widgetSanitization
     */
    public function __construct(
        AccountService $accountService,
        PersistenceManager $persistenceManager,
        UserRepository $userRepository,
        WidgetSanitization $widgetSanitization
    ) {
        $this->accountService = $accountService;
        $this->persistenceManager = $persistenceManager;
        $this->userRepository = $userRepository;
        $this->widgetSanitization = $widgetSanitization;
    }

    /**
     * @param array $arguments
     * @return User|null
     * @throws IllegalObjectTypeException
     */
    public function getUser(array $arguments): ?User
    {
        $user = null;
        $accountData = $this->accountService->getAccountDataByArguments($arguments);
        $accountId = $this->accountService->getAccountId();

        if ($accountId > 0 && is_array($accountData)) {
            $user = $this->findUser($accountId);
        }

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws \JsonException
     */
    public function updateUser(User $user): User
    {
        $hasChanges = false;
        $data = FileUtility::getContent();

        if (is_array($data['widgets'])) {
            $hasChanges = true;
            $dashboardWidgets = implode(',', $this->widgetSanitization->sanitize($data['widgets']));

            $user->setDashboardWidgets($dashboardWidgets);
        }

        if ($hasChanges) {
            $this->userRepository->update($user);
            $this->persistenceManager->persistAll();
        }

        return $user;
    }

    /**
     * @param int $accountId
     * @return User
     * @throws IllegalObjectTypeException
     */
    protected function findUser(int $accountId): User
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneByAccountId($accountId);

        if ($user === null) {
            $user = $this->createUser($accountId);
        }

        return $user;
    }

    /**
     * @param int $accountId
     * @return User
     * @throws IllegalObjectTypeException
     */
    protected function createUser(int $accountId): User
    {
        /** @var User $user */
        $user = GeneralUtility::makeInstance(User::class);
        $user->setAccountId($accountId);

        $this->userRepository->add($user);
        $this->persistenceManager->persistAll();

        return $user;
    }
}
