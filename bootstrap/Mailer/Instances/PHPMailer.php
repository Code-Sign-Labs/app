<?php

namespace Framework\Mailer\Instances;

use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Mailer\Interfaces\MailerStrategyInterface;
use Framework\Mailer\MailerEnum;
use Framework\Mailer\ValueObjects\AttachmentsData;
use Framework\Mailer\ValueObjects\AuthenticationCredentials;
use Framework\Mailer\ValueObjects\AuthorData;
use Framework\Mailer\ValueObjects\ReplyData;
use Framework\Mailer\ValueObjects\TargetsData;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer as PHPMailerInstance;

class PHPMailer implements MailerStrategyInterface
{
    public function __construct(protected ViewEngineInterface $viewEngine)
    {
    }

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
     * @throws Exception
     */
    public function send(AuthorData $authorData, TargetsData $targetsData, AuthenticationCredentials $authenticationCredentials,string $subject, string $templateName, array $templateVariables, string $altMessage = '', AttachmentsData $attachmentsData = null, ReplyData $replyData = null, bool $debug = false): true
    {
        $mailInstance = new PHPMailerInstance();

        // Debug
        if($debug) {
            $mailInstance->SMTPDebug = $debug;
        }

        // Authentication
        $mailInstance->SMTPAuth = $authenticationCredentials->isUseSMTP();
        $mailInstance->Host = $authenticationCredentials->getHost();
        $mailInstance->Port = $authenticationCredentials->getPort();
        $mailInstance->Username = $authenticationCredentials->getUsername();
        $mailInstance->Password = $authenticationCredentials->getUsername();
        $encryptionType = $authenticationCredentials->getEncryptionType();
        if($encryptionType !== 'ssl' && $encryptionType !== 'tls') {
            throw new Exception("Invalid Encryption Type");
        }
        $mailInstance->SMTPSecure = $authenticationCredentials->getEncryptionType();

        // From
        $mailInstance->setFrom($authenticationCredentials->getEmail(), $authorData->getAuthorName());

        // Targets
        $targets = $targetsData->getTargets();
        if(count($targets) == 0) {
            throw new Exception("You need to pass minimum one Target via TargetsData");
        }
        foreach($targets as $target) {
            $mailInstance->addAddress($target->getEmail(), $target->getName() ?? '');
        }

        // Reply
        if($replyData) {
            foreach($replyData as $reply) {
                $mailInstance->addReplyTo($reply->getEmail(), $reply->getName() ?? '');
            }
        }

        // Attachments
        if($attachmentsData) {
            foreach($attachmentsData as $attachment) {
                $mailInstance->addAttachment($attachment->getPath(), $attachment->getName(), $attachment->getEncoding());
            }
        }

        // Main Body
        $mailInstance->isHTML(true);
        $mailInstance->Subject = $subject;
        $mailInstance->Body = $this->viewEngine->render($templateName, $templateVariables);
        $mailInstance->AltBody = $altMessage ?? MailerEnum::HTML_RENDER_ERROR;

        // Sent Email
        $mailInstance->send();
        return true;
    }
}