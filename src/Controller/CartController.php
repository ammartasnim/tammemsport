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
use Symfony\Component\HttpFoundation\Request;


final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function cart(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->redirectToRoute('app_cart_checkout');
    }

    #[Route('/cart/mini', name: 'app_cart_mini')]
    public function mini(PanierRepository $panierRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        $items = $panier ? $panier->getItems() : [];
        $cartWithData = [];
        $total = 0;

        foreach ($items as $item) {
            $produit = $item->getProduit();
            $quantity = $item->getQuantite();
            if ($produit) {
                $cartWithData[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                ];
                $total += $produit->getPrix() * $quantity;
            }
        }

        return $this->render('cart/_mini_cart.html.twig', [
            'cart' => $cartWithData,
            'total' => $total,
            'cartCount' => $this->calculateCount($panier),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $produit = $produitRepository->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('The product does not exist');
        }

        if ($produit->getStock() <= 0) {
            $this->addFlash('warning', 'Produit en rupture de stock.');
            if ($this->isAjaxRequest($request)) {
                $panier = $panierRepository->findActiveByUser($this->getUser());
                return $this->render('cart/_mini_cart.html.twig', [
                    'cart' => $this->buildCartData($panier),
                    'total' => $this->calculateTotal($panier),
                    'cartCount' => $this->calculateCount($panier),
                ]);
            }
            return $this->redirectToRoute('app_product_catalog');
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
            if ($item->getQuantite() >= $produit->getStock()) {
                $this->addFlash('warning', 'Stock insuffisant pour augmenter la quantite.');
                if ($this->isAjaxRequest($request)) {
                    return $this->render('cart/_mini_cart.html.twig', [
                        'cart' => $this->buildCartData($panier),
                        'total' => $this->calculateTotal($panier),
                        'cartCount' => $this->calculateCount($panier),
                    ]);
                }
                return $this->redirect($this->getReturnUrl($request));
            }
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

        if ($this->isAjaxRequest($request)) {
            return $this->render('cart/_mini_cart.html.twig', [
                'cart' => $this->buildCartData($panier),
                'total' => $this->calculateTotal($panier),
                'cartCount' => $this->calculateCount($panier),
            ]);
        }

        $redirectUrl = $request->headers->get('referer') ?? $this->generateUrl('app_product_catalog');
        return $this->redirect($redirectUrl);
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier) {
            if ($this->isAjaxRequest($request)) {
                return $this->render('cart/_mini_cart.html.twig', [
                    'cart' => [],
                    'total' => 0,
                    'cartCount' => 0,
                ]);
            }
            return $this->redirect($this->getReturnUrl($request));
        }

        $produit = $produitRepository->find($id);
        if (!$produit) {
            if ($this->isAjaxRequest($request)) {
                return $this->render('cart/_mini_cart.html.twig', [
                    'cart' => $this->buildCartData($panier),
                    'total' => $this->calculateTotal($panier),
                    'cartCount' => $this->calculateCount($panier),
                ]);
            }
            return $this->redirect($this->getReturnUrl($request));
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

        if ($this->isAjaxRequest($request)) {
            $panier = $panierRepository->findActiveByUser($this->getUser());
            return $this->render('cart/_mini_cart.html.twig', [
                'cart' => $this->buildCartData($panier),
                'total' => $this->calculateTotal($panier),
                'cartCount' => $this->calculateCount($panier),
            ]);
        }

        return $this->redirect($this->getReturnUrl($request));
    }

    #[Route('/cart/delete/{id}', name: 'app_cart_delete')]
    public function delete(int $id, PanierRepository $panierRepository, PanierItemRepository $panierItemRepository, ProduitRepository $produitRepository, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier) {
            if ($this->isAjaxRequest($request)) {
                return $this->render('cart/_mini_cart.html.twig', [
                    'cart' => [],
                    'total' => 0,
                    'cartCount' => 0,
                ]);
            }
            return $this->redirect($this->getReturnUrl($request));
        }

        $produit = $produitRepository->find($id);
        if (!$produit) {
            if ($this->isAjaxRequest($request)) {
                return $this->render('cart/_mini_cart.html.twig', [
                    'cart' => $this->buildCartData($panier),
                    'total' => $this->calculateTotal($panier),
                    'cartCount' => $this->calculateCount($panier),
                ]);
            }
            return $this->redirect($this->getReturnUrl($request));
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

        if ($this->isAjaxRequest($request)) {
            $panier = $panierRepository->findActiveByUser($this->getUser());
            return $this->render('cart/_mini_cart.html.twig', [
                'cart' => $this->buildCartData($panier),
                'total' => $this->calculateTotal($panier),
                'cartCount' => $this->calculateCount($panier),
            ]);
        }

        return $this->redirect($this->getReturnUrl($request));
    }

    #[Route('/cart/empty', name: 'app_cart_empty')]
    public function empty(PanierRepository $panierRepository, EntityManagerInterface $em, Request $request): Response
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

        if ($this->isAjaxRequest($request)) {
            return $this->render('cart/_mini_cart.html.twig', [
                'cart' => [],
                'total' => 0,
                'cartCount' => 0,
            ]);
        }

        return $this->redirect($this->getReturnUrl($request));
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['GET'])]
    public function checkout(PanierRepository $panierRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());
        if (!$panier || $panier->getItems()->count() === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_product_catalog');
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $this->buildCartData($panier),
            'total' => $this->calculateTotal($panier),
            'cartCount' => $this->calculateCount($panier),
        ]);
    }

    #[Route('/checkout/confirm', name: 'app_cart_checkout_confirm', methods: ['POST'])]
    public function confirm(
        PanierRepository $panierRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $panier = $panierRepository->findActiveByUser($this->getUser());

        if (!$panier || $panier->getItems()->count() === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_product_catalog');
        }

        $items = $panier->getItems();

        foreach ($items as $item) {
            $produit = $item->getProduit();
            if (!$produit) {
                continue;
            }

            $quantity = $item->getQuantite();
            $stock = $produit->getStock();
            if ($stock < $quantity) {
                $this->addFlash('warning', sprintf(
                    'Stock insuffisant pour %s (stock: %d, demande: %d).',
                    $produit->getNom(),
                    $stock,
                    $quantity
                ));
                return $this->redirectToRoute('app_cart_checkout');
            }
        }

        $commande = new Commande();
        $commande->setUser($this->getUser());
        $commande->setDate(new DateTime());

        $total = 0;

        foreach ($items as $item) {
            $produit = $item->getProduit();
            if ($produit) {
                $quantity = $item->getQuantite();
                $ligne = new LigneCommande();
                $ligne->setProduit($produit);
                $ligne->setQuantite($quantity);
                $ligne->setPrix($produit->getPrix());
                $ligne->setCommande($commande);
                $newStock = $produit->getStock() - $quantity;
                $produit->setStock($newStock);
                $em->persist($produit);

                $total += ($produit->getPrix() * $quantity);
                $em->persist($ligne);
            }
        }

        $commande->setTotal($total);
        $em->persist($commande);

        $panier->setStatus('checked_out');
        $panier->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($panier);

        $em->flush();

        $this->addFlash('success', 'Votre commande a ete confirmee.');

        return $this->redirectToRoute('app_orders');
    }

    private function buildCartData(?Panier $panier): array
    {
        if (!$panier) {
            return [];
        }

        $cartWithData = [];
        foreach ($panier->getItems() as $item) {
            $produit = $item->getProduit();
            $quantity = $item->getQuantite();
            if ($produit) {
                $cartWithData[] = [
                    'produit' => $produit,
                    'quantity' => $quantity,
                ];
            }
        }

        return $cartWithData;
    }

    private function calculateTotal(?Panier $panier): float
    {
        if (!$panier) {
            return 0;
        }

        $total = 0;
        foreach ($panier->getItems() as $item) {
            $produit = $item->getProduit();
            $quantity = $item->getQuantite();
            if ($produit) {
                $total += $produit->getPrix() * $quantity;
            }
        }

        return $total;
    }

    private function calculateCount(?Panier $panier): int
    {
        if (!$panier) {
            return 0;
        }

        $count = 0;
        foreach ($panier->getItems() as $item) {
            $count += $item->getQuantite();
        }

        return $count;
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || $request->query->getBoolean('ajax')
            || $request->headers->get('Accept') === 'text/vnd.turbo-stream.html';
    }

    private function getReturnUrl(Request $request): string
    {
        return $request->headers->get('referer') ?? $this->generateUrl('app_product_catalog');
    }
}
