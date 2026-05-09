<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class ProductController extends AbstractController
{
    #[Route('/catalog', name: 'app_product_catalog')]
    public function catalog(ProduitRepository $produitRepository, Request $request): Response
    {
        $searchTerm=$request->query->get('q');
        if($searchTerm){
            $produits=$produitRepository->findBySearchTerm($searchTerm);
        }else{
            $produits=$produitRepository->findAll();
        }

        return $this->render('product/catalog.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_details')]
    public function productDetails(int $id, ProduitRepository $produitRepository): Response
    {
        $produit = $produitRepository->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('The product does not exist');
        }
        return $this->render('product/productDetails.html.twig', [
            'produit' => $produit,
        ]);
    }
}
