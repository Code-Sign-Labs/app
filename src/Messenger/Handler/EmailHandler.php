<?php
declare(strict_types=1);
namespace App\Messenger\Handler;

use App\Extension\CoreExtension;
use Framework\Messenger\Interfaces\MessageHandlerInterface;
use Framework\Messenger\Objects\Envelope;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class EmailHandler implements MessageHandlerInterface
{
    public function handle(Envelope $envelope): bool
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../../storage/templates/');
        $twig   = new Environment($loader);
        $twig->addExtension(new CoreExtension());
        $targetEmail    = $envelope->getStamps()['email'];
        $additionalData = $envelope->getStamps()['additionalData'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MESSENGER_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MESSENGER_MAIL'];
            $mail->Password   = $_ENV['MESSENGER_MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int) $_ENV['MESSENGER_PORT'];

            $mail->setFrom($_ENV['MESSENGER_MAIL'], $_ENV['MESSENGER_NAME']);
            $mail->addAddress($targetEmail);

            $mail->isHTML(true);
            $mail->Subject = $additionalData['subject'];
            $mail->Body    = $twig->render($additionalData['template'] . '.twig', $additionalData['variables']);
            $mail->AltBody = $twig->render($additionalData['template'] . '_alt.twig', $additionalData['variables']);

            $mail->send();

            return true;
        } catch (\Exception $exception) {
            // Log the error message
            error_log('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo . '\n' . $exception->getMessage());

            return false;
        }
    }
}
