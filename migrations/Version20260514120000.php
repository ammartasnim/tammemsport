<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes on produit and panier tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_produit_nom ON produit (nom)');
        $this->addSql('CREATE INDEX idx_produit_is_promo ON produit (is_promo)');
        $this->addSql('CREATE INDEX idx_panier_status ON panier (status)');
        $this->addSql('CREATE INDEX idx_panier_user_status ON panier (user_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_produit_nom ON produit');
        $this->addSql('DROP INDEX idx_produit_is_promo ON produit');
        $this->addSql('DROP INDEX idx_panier_status ON panier');
        $this->addSql('DROP INDEX idx_panier_user_status ON panier');
    }
}
