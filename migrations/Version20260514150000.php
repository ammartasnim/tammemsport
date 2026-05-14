<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260514150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change commande.total from int to float for promo price precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande CHANGE total total DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande CHANGE total total INT DEFAULT NULL');
    }
}
