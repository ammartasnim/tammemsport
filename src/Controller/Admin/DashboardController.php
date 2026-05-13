<?php

namespace App\Controller\Admin;

use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly ProduitRepository $produitRepository,
        private readonly UserRepository $userRepository
    )
    {
    }

    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // return $this->redirectToRoute('admin_user_index');

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        $since = new \DateTimeImmutable('-30 days');

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $this->userRepository->countAll(),
            'newUsers' => $this->userRepository->countCreatedSince($since),
            'totalOrders' => $this->commandeRepository->countAll(),
            'ordersLast30Days' => $this->commandeRepository->countSince($since),
            'totalRevenue' => $this->commandeRepository->sumRevenue(),
            'totalProducts' => $this->produitRepository->countAll(),
        ]);
    }

    #[Route('/admin/export/monthly-report', name: 'admin_export_monthly_report', methods: ['GET'])]
    public function exportMonthlyReport(): StreamedResponse
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $startOfNextMonth = $startOfMonth->modify('+1 month');

        $ordersCount = $this->commandeRepository->countBetween($startOfMonth, $startOfNextMonth);
        $revenue = $this->commandeRepository->sumRevenueBetween($startOfMonth, $startOfNextMonth);
        $newUsers = $this->userRepository->countCreatedSince($startOfMonth);

        $response = new StreamedResponse(function () use ($startOfMonth, $ordersCount, $revenue, $newUsers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report Month', $startOfMonth->format('Y-m')]);
            fputcsv($handle, ['Total Orders', $ordersCount]);
            fputcsv($handle, ['Total Revenue', number_format($revenue, 2, '.', '')]);
            fputcsv($handle, ['New Users', $newUsers]);
            fclose($handle);
        });

        $filename = sprintf('monthly-report-%s.csv', $startOfMonth->format('Y-m'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(ContactMessageCrudController::class, 'Contact Messages', 'far fa-envelope');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fas fa-list');
        yield MenuItem::linkTo(CategorieCrudController::class, 'Categories', 'fas fa-list');
        yield MenuItem::linkTo(ProduitCrudController::class, 'Produits', 'fas fa-list');
    }
}
