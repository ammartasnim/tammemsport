<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function account(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Informations du profil mises a jour.');

            return $this->redirectToRoute('app_account');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = $passwordForm->get('newPassword')->getData();
            $confirmPassword = $passwordForm->get('confirmPassword')->getData();

            if ($newPassword !== $confirmPassword) {
                $passwordForm->get('confirmPassword')->addError(new FormError('Les mots de passe ne correspondent pas.'));
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Mot de passe mis a jour.');

                return $this->redirectToRoute('app_account');
            }
        }

        return $this->render('account/account.html.twig', [
            'user' => $user,
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

    #[Route('/orders', name: 'app_orders')]
    public function orders(CommandeRepository $commandeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $orders = $commandeRepository->findBy(
            ['user' => $this->getUser()],
            ['date' => 'DESC']
        );

        return $this->render('account/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
