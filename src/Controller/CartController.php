<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitRepository;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Panier;
use App\Entity\PanierItem;
use App\Repository\PanierItemRepository;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;


final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function cart(PanierRepository $panierRepository, ProduitRepository $produitRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        $items = $panier ? $panier->getItems() : [];
        $cartWithData=[];
        $total=0;
        foreach($items as $item){
            $produit = $item->getProduit();
            $quantity = $item->getQuantite();
            if ($produit) {
                $cartWithData[]=[
                    'produit'=>$produit,
                    'quantity'=>$quantity
                ];
                $total+=$produit->getPrix()*$quantity;
            }
        }

        return $this->render('cart/cart.html.twig', [
            'cart' => $cartWithData,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $produit = $produitRepository->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('The product does not exist');
        }

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($this->getUser());
            $em->persist($panier);
        }

        $item = $panierItemRepository->findOneBy([
            'panier' => $panier,
            'produit' => $produit,
        ]);

        if ($item) {
            $item->setQuantite($item->getQuantite() + 1);
        } else {
            $item = new PanierItem();
            $item->setPanier($panier);
            $item->setProduit($produit);
            $item->setQuantite(1);
            $em->persist($item);
        }

        $panier->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier) {
            return $this->redirectToRoute('app_cart');
        }

        $produit = $produitRepository->find($id);
        if (!$produit) {
            return $this->redirectToRoute('app_cart');
        }

        $item = $panierItemRepository->findOneBy([
            'panier' => $panier,
            'produit' => $produit,
        ]);

        if ($item) {
            if ($item->getQuantite() > 1) {
                $item->setQuantite($item->getQuantite() - 1);
            } else {
                $em->remove($item);
            }

            $panier->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/delete/{id}', name: 'app_cart_delete')]
    public function delete(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier) {
            return $this->redirectToRoute('app_cart');
        }

        $produit = $produitRepository->find($id);
        if (!$produit) {
            return $this->redirectToRoute('app_cart');
        }

        $item = $panierItemRepository->findOneBy([
            'panier' => $panier,
            'produit' => $produit,
        ]);

        if ($item) {
            $em->remove($item);
            $panier->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/empty', name: 'app_cart_empty')]
    public function empty(PanierRepository $panierRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if ($panier) {
            foreach ($panier->getItems() as $item) {
                $em->remove($item);
            }
            $panier->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
public function checkout(
    PanierRepository $panierRepository,
    EntityManagerInterface $em
): Response {
    // 1. Must be logged in
    $this->denyAccessUnlessGranted('ROLE_USER');

    $panier = $panierRepository->findActiveByUser($this->getUser());

    if (!$panier || $panier->getItems()->count() === 0) {
        $this->addFlash('warning', 'Votre panier est vide.');
        return $this->redirectToRoute('app_product_catalog');
    }

    $items = $panier->getItems();

    // 2. Create the main Order (Commande)
    $commande = new Commande();
    $commande->setUser($this->getUser()); // Links to the logged-in user
    $commande->setDate(new DateTime());
    
    $total = 0;

    // 3. Create Order Lines (LigneCommande)
    foreach ($items as $item) {
        $produit = $item->getProduit();
        if ($produit) {
            $quantity = $item->getQuantite();
            $ligne = new LigneCommande();
            $ligne->setProduit($produit);
            $ligne->setQuantite($quantity);
            $ligne->setPrix($produit->getPrix());
            $ligne->setCommande($commande); // Link to the main order
            $newStock = $produit->getStock() - $quantity;
            $produit->setStock($newStock);
            $em->persist($produit); // Doctrine already tracks this, but it doesn't hurt.
            
            $total += ($produit->getPrix() * $quantity);
            $em->persist($ligne);
        }
    }

    $commande->setTotal($total);
    $em->persist($commande);
    $em->flush(); // Saves everything to the DB in one go!

    // 4. Mark cart as checked out
    $panier->setStatus('checked_out');
    $panier->setUpdatedAt(new \DateTimeImmutable());
    $em->persist($panier);

    return $this->redirectToRoute('app_product_catalog');
}
}
