<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
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
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $userService->register($user, $plainPassword);
            $this->addFlash('success', 'Вы успешно зарегистрировались.');
            return $security->login($user);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/email/confirmation/{token}', name: 'email_confirmation')]
    public function verifyUserEmail(string $token, UserService $userService): Response
    {
        if ($userService->confirmEmailIfValid($token)) {
            $this->addFlash('success', 'Вы успешно подтвердили свою почту.');
        } else {
            $this->addFlash('warning', 'Ссылка подтверждения недействительна или срок её действия истёк.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
