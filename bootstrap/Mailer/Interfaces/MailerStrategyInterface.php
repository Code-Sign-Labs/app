<?php

namespace Framework\Mailer\Interfaces;

use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Mailer\ValueObjects\AttachmentsData;
use Framework\Mailer\ValueObjects\AuthenticationCredentials;
use Framework\Mailer\ValueObjects\AuthorData;
use Framework\Mailer\ValueObjects\ReplyData;
use Framework\Mailer\ValueObjects\TargetsData;

interface MailerStrategyInterface
{
    /**
     * @param ViewEngineInterface $viewEngine
     */
    public function __construct(ViewEngineInterface $viewEngine);

    /**
     * @param AuthorData $authorData
     * @param TargetsData $targetsData
     * @param AuthenticationCredentials $authenticationCredentials
     * @param string $subject
     * @param string $templateName
     * @param array $templateVariables
     * @param string $altMessage
     * @param AttachmentsData|null $attachmentsData
     * @param ReplyData|null $replyData
     * @param bool $debug
     * @return true
     */
    public function send(AuthorData $authorData, TargetsData $targetsData, AuthenticationCredentials $authenticationCredentials,string $subject, string $templateName, array $templateVariables, string $altMessage = '', AttachmentsData $attachmentsData = null, ReplyData $replyData = null, bool $debug = false): true;
}