<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE on panier_item.produit_id FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F347EFB');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F347EFB');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
    }
}
