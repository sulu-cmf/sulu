<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This controller handles everything a user is allowed to change on its own.
 */
class ProfileController implements ClassResourceInterface
{
    protected static $entityNameUserSetting = UserSetting::class;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ObjectManager $objectManager,
        private ViewHandlerInterface $viewHandler,
        private UserSettingRepositoryInterface $userSettingRepository,
        private UserManagerInterface $userManager,
        private string $userClass,
        private string $contactClass,
    ) {
    }

    /**
     * Gets the profile information of a user.
     *
     * @return Response
     */
    public function getAction()
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $view = View::create($user);

        $context = new Context();
        $context->setGroups(['profile']);
        $context->setSerializeNull(false); // required for sub entity as twoFactor/method can else not be written

        $view->setContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the given profile information of a user.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function putAction(Request $request)
    {
        $this->checkArguments($request);
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $this->userManager->save($this->getData($request), $request->get('locale'), $user->getId(), true);

        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));

        if ($user instanceof TwoFactorInterface) {
            /** @var array{method?: string|null} $twoFactorData */
            $twoFactorData = $request->request->all('twoFactor');
            $twoFactorMethod = $twoFactorData['method'] ?? null;

            if ($twoFactorMethod) {
                $twoFactor = $user->getTwoFactor();
                if (!$twoFactor) {
                    $twoFactor = new UserTwoFactor($user);
                }

                $twoFactor->setMethod($twoFactorMethod);
                $user->setTwoFactor($twoFactor);
            } else {
                $twoFactor = $user->getTwoFactor();
                if ($twoFactor) {
                    $user->setTwoFactor(null);
                    $this->objectManager->remove($twoFactor);
                }
            }
        }

        $this->objectManager->flush();

        $view = View::create($user);

        $context = new Context();
        $context->setGroups(['profile']);

        $view->setContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Takes a key, value pair and stores it as settings for the user.
     *
     * @return Response
     */
    public function patchSettingsAction(Request $request)
    {
        $settings = $request->request->all();

        try {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            foreach ($settings as $settingKey => $settingValue) {
                // get setting
                // TODO: move this logic into own service (UserSettingManager?)
                $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => $settingKey]);

                // or create new one
                if (!$setting) {
                    $setting = new UserSetting();
                    $setting->setKey($settingKey);
                    $setting->setUser($user);
                    $this->objectManager->persist($setting);
                }

                // persist setting
                $setting->setValue(\json_encode($settingValue));
            }
            $this->objectManager->flush();

            //create view
            $view = View::create($settings, 200);
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes a user setting by a given key.
     *
     * @return Response
     */
    public function deleteSettingsAction(Request $request)
    {
        $key = $request->get('key');

        try {
            if (!$key) {
                throw new MissingArgumentException(static::$entityNameUserSetting, 'key');
            }

            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            // get setting
            // TODO: move this logic into own service (UserSettingManager?)
            $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => $key]);

            if ($setting) {
                $this->objectManager->remove($setting);
                $this->objectManager->flush();
                $view = View::create(null, 204);
            } else {
                $view = View::create(null, 400);
            }
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Checks the arguments of the given request.
     *
     * @throws MissingArgumentException
     */
    private function checkArguments(Request $request)
    {
        if (null === $request->get('firstName')) {
            throw new MissingArgumentException($this->contactClass, 'firstName');
        }
        if (null === $request->get('lastName')) {
            throw new MissingArgumentException($this->contactClass, 'lastName');
        }
        if (null === $request->get('username')) {
            throw new MissingArgumentException($this->userClass, 'username');
        }
        if (null === $request->get('email')) {
            throw new MissingArgumentException($this->userClass, 'email');
        }
        if (null === $request->get('locale')) {
            throw new MissingArgumentException($this->userClass, 'locale');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(Request $request): array
    {
        $data = [];

        foreach ($request->request->all() as $key => $value) {
            if (\in_array($key, ['firstName', 'lastName', 'username', 'email', 'password', 'locale'], true)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
