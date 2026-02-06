<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206213615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE case_photo (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) NOT NULL, caption VARCHAR(140) DEFAULT NULL, uploaded_at DATETIME NOT NULL, case_social_id INT NOT NULL, INDEX IDX_465E7EF4D1C82BDC (case_social_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE case_social (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT NOT NULL, cha9a9a_link VARCHAR(255) NOT NULL, is_urgent TINYINT NOT NULL, views_count INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, categorie_id INT NOT NULL, publisher_id INT NOT NULL, INDEX IDX_D88FA62EBCF5E72D (categorie_id), INDEX IDX_D88FA62E40C86FCE (publisher_id), INDEX idx_case_created_at (created_at), INDEX idx_case_urgent (is_urgent), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(100) DEFAULT NULL, UNIQUE INDEX uniq_categorie_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 3) NOT NULL, donated_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, reference VARCHAR(120) DEFAULT NULL, note LONGTEXT DEFAULT NULL, donor_id INT NOT NULL, case_social_id INT NOT NULL, INDEX IDX_31E581A03DD7B7A7 (donor_id), INDEX IDX_31E581A0D1C82BDC (case_social_id), INDEX idx_donation_donated_at (donated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT DEFAULT NULL, start_at DATETIME NOT NULL, location VARCHAR(160) DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_B26681EB03A8386 (created_by_id), INDEX idx_event_start_at (start_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(120) DEFAULT NULL, UNIQUE INDEX uniq_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE case_photo ADD CONSTRAINT FK_465E7EF4D1C82BDC FOREIGN KEY (case_social_id) REFERENCES case_social (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE case_social ADD CONSTRAINT FK_D88FA62EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE case_social ADD CONSTRAINT FK_D88FA62E40C86FCE FOREIGN KEY (publisher_id) REFERENCES `user` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A03DD7B7A7 FOREIGN KEY (donor_id) REFERENCES `user` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0D1C82BDC FOREIGN KEY (case_social_id) REFERENCES case_social (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE case_photo DROP FOREIGN KEY FK_465E7EF4D1C82BDC');
        $this->addSql('ALTER TABLE case_social DROP FOREIGN KEY FK_D88FA62EBCF5E72D');
        $this->addSql('ALTER TABLE case_social DROP FOREIGN KEY FK_D88FA62E40C86FCE');
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A03DD7B7A7');
        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0D1C82BDC');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EB03A8386');
        $this->addSql('DROP TABLE case_photo');
        $this->addSql('DROP TABLE case_social');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE donation');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
