<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home_redirect')]
    public function redirectToHome(): Response
    {
        return $this->redirectToRoute('home');
    }

    #[Route('/home', name: 'home')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $promos = $produitRepository->findPromos(6);
        $latest = [];

        if (!$promos) {
            $latest = $produitRepository->findLatest(6);
        }

        return $this->render('home/index.html.twig', [
            'promos' => $promos,
            'latest' => $latest,
        ]);
    }
}
