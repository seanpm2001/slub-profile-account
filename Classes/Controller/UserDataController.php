<?php

declare(strict_types=1);

/*
 * This file is part of the package slub/slub-profile-account
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Slub\SlubProfileAccount\Controller;

use Psr\Http\Message\ResponseInterface;
use Slub\SlubProfileAccount\Domain\Model\User\Account as User;
use Slub\SlubProfileAccount\Mvc\View\JsonView;
use Slub\SlubProfileAccount\Service\UserDataService as UserService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class UserDataController extends ActionController
{
    protected $view;
    protected $defaultViewObjectName = JsonView::class;
    protected ?User $user;
    protected UserService $userService;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function downloadAction(): ResponseInterface
    {
        $dataDownload = $this->userService->getDownload(
            $this->request->getArguments()
        );

        $this->view->setVariablesToRender(['userDataDownload']);
        $this->view->assign('userDataDownload', $dataDownload);

        return $this->jsonResponse();
    }
}
