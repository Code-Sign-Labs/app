<?php

namespace Framework\Mailer\ValueObjects;

class AttachmentsData
{
    /**
     * @var array $attachments
     */
    protected array $attachments;

    /**
     * @param Attachment $attachment
     * @return void
     */
    public function addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}