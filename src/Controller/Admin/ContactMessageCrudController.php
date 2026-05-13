<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\HttpFoundation\Response;

class ContactMessageCrudController extends AbstractCrudController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, Action::new('toggleRead', 'Toggle read')->linkToCrudAction('toggleRead'))
            ->add(Crud::PAGE_DETAIL, Action::new('toggleRead', 'Toggle read')->linkToCrudAction('toggleRead'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            EmailField::new('email'),
            TextField::new('subject'),
            AssociationField::new('user')->hideOnForm(),
            TextareaField::new('message')->onlyOnDetail(),
            TextareaField::new('message')->onlyOnIndex(),
            DateTimeField::new('createdAt')->hideOnForm(),
            BooleanField::new('isRead'),
        ];
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $entity = $context->getEntity()->getInstance();
        if ($entity instanceof ContactMessage && !$entity->isRead()) {
            $entity->setIsRead(true);
            $this->entityManager->flush();
        }

        return parent::detail($context);
    }

    #[AdminRoute]
    public function toggleRead(AdminContext $context): Response
    {
        $entity = $context->getEntity()->getInstance();
        if ($entity instanceof ContactMessage) {
            $entity->setIsRead(!$entity->isRead());
            $this->entityManager->flush();
        }

        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl($context->getDashboardRouteName()));
    }
}
