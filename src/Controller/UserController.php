<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\User\ForgotPasswordFormType;
use App\Form\User\EditPasswordFormType;
use App\Form\User\RegistrationFormType;
use App\Form\User\RestorePasswordFormType;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{
    #[Route('/profile/{username}', name: 'profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(User $user): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'register')]
    public function register(Request $request, Security $security, UserService $userService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $userService->register($user, $plainPassword);
            $this->addFlash('success', 'Вы успешно зарегистрировались.');
            return $security->login($user);
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/forgotPassword', name: 'forgot_password')]
    public function forgotPassword(Request $request, UserService $userService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->sendConfirmationToken($form->getData()['email']);
            $this->addFlash('success', 'Если почта верна, то на нее будет отправлено письмо для восстановления пароля.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/forgot_password.html.twig', [
            'forgotPasswordForm' => $form,
        ]);
    }

    #[Route('/restorePassword/{token}', name: 'restore_password')]
    public function restorePassword($token, Request $request, UserService $userService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        if ($user = $userService->tokenIsValid($token)) {
            $form = $this->createForm(RestorePasswordFormType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userService->updatePassword($user, $form->get('plainPassword')->getData());

                $this->addFlash('success', 'Ваш пароль изменен, можете войти в аккаунт используя новый пароль.');
                return $this->redirectToRoute('app_home');
            }

            return $this->render('user/restore_password.html.twig', [
                'restorePasswordForm' => $form,
            ]);
        } else {
            $this->addFlash('success', 'Ссылка восстановления пароля недействительна или срок её действия истёк, повторите попытку.');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/editPassword', name: 'edit_password')]
    #[IsGranted('ROLE_USER')]
    public function editPassword(#[CurrentUser] User $user, Request $request, UserService $userService): Response
    {
        $form = $this->createForm(EditPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($userService->isPasswordValid($user,$form->get('current')->getData())) {
                $userService->updatePassword($user, $form->get('plainPassword')->getData());
                $this->addFlash('success', 'Ваш пароль успешно изменен');

                return $this->redirectToRoute('user_profile', ['username' => $user->getUsername()]);
            } else {
                $form->get('current')->addError(new FormError('Текущий пароль указан неправильно'));
            }
        }

        return $this->render('user/edit_password.html.twig', [
            'restorePasswordForm' => $form,
        ]);
    }

    #[Route('/email/confirmation/{token}', name: 'email_confirmation')]
    public function verifyUserEmail(string $token, UserService $userService): Response
    {
        if ($userService->confirmEmailIfTokenIsValid($token)) {
            $this->addFlash('success', 'Вы успешно подтвердили свою почту.');
        } else {
            $this->addFlash('warning', 'Ссылка подтверждения электронной почты недействительна или срок её действия истёк, повторите попытку.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
