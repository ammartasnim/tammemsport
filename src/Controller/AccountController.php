<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function account(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('account/account.html.twig', [
            'user' => $this->getUser(),
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
