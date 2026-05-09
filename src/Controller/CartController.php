<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;


final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function cart(RequestStack $requestStack, ProduitRepository $produitRepository): Response
    {
        $session = $requestStack->getSession();
        $cart = $session->get('cart', []);
        $cartWithData=[];
        $total=0;
        foreach($cart as $id=>$quantity){
            $produit=$produitRepository->find($id);
            if($produit){
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
    public function add(int $id, RequestStack $requestStack, ProduitRepository $produitRepository): Response
    {
        $session=$requestStack->getSession();
        $cart=$session->get('cart', []);
        if(!empty($cart[$id])){
            $cart[$id]++;
        }else{
            $cart[$id]=1;
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/delete/{id}', name: 'app_cart_delete')]
    public function delete(int $id, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/empty', name: 'app_cart_empty')]
    public function empty(RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $session->remove('cart');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
public function checkout(
    RequestStack $requestStack, 
    ProduitRepository $produitRepository, 
    EntityManagerInterface $em
): Response {
    // 1. Must be logged in
    $this->denyAccessUnlessGranted('ROLE_USER');

    $session = $requestStack->getSession();
    $cart = $session->get('cart', []);

    if (empty($cart)) {
        $this->addFlash('warning', 'Votre panier est vide.');
        return $this->redirectToRoute('app_product_catalog');
    }

    // 2. Create the main Order (Commande)
    $commande = new Commande();
    $commande->setUser($this->getUser()); // Links to the logged-in user
    $commande->setDate(new DateTime());
    
    $total = 0;

    // 3. Create Order Lines (LigneCommande)
    foreach ($cart as $id => $quantity) {
        $produit = $produitRepository->find($id);
        if ($produit) {
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

    // 4. Clear the session cart
    $session->remove('cart');

    $this->addFlash('success', 'Merci ! Votre commande a été enregistrée.');
    return $this->redirectToRoute('app_product_catalog');
}
}
