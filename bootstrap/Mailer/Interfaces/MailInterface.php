<?php
declare(strict_types=1);
namespace Framework\Mailer\Interfaces;

interface MailInterface
{
    public function setRecipient(string $to): self;
    public function setSubject(string $subject): self;
    public function setBody(string $body): self;
    public function send(): bool;
}