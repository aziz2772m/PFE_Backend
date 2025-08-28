<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
class NotificationService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    // Quand le chercheur soumet un papier
    public function notifyAdminPaperSubmitted(
        string $adminEmail,
        string $paperTitle,
        string $authorName,
        ?string $absoluteFilePath = null
    ): void {
        $email = (new TemplatedEmail())
            ->from('noreply@tonapp.com')
            ->to($adminEmail)
            ->subject('Nouveau papier soumis')
            ->text("Le papier '{$paperTitle}' a été soumis par {$authorName}")
            ->context([
                'authorName' => $authorName,
                'paperTitle' => $paperTitle,
            ]);

        if ($absoluteFilePath && file_exists($absoluteFilePath)) {
            $email->attachFromPath($absoluteFilePath);
        }

        $this->mailer->send($email);
    }
    // Quand l’admin assigne un éditeur
    public function notifyEditorAssigned(string $editorEmail, string $paperTitle): void
    {
        $email = (new Email())
            ->from('noreply@tonapp.com')
            ->to($editorEmail)
            ->subject('Vous avez été assigné à un papier')
            ->text(sprintf("Vous avez été assigné à l'évaluation du papier : %s", $paperTitle))
            ->html(sprintf("<p>Bonjour,</p><p>Vous avez été assigné à l'évaluation du papier : <strong>%s</strong></p>", $paperTitle));

        $this->mailer->send($email);
    }


     // ✅ Quand l’admin assigne un avis final au chercheur
    public function notifyResearcherFinalDecision(
        string $researcherEmail,
        string $paperTitle,
        string $finalDecision,
        ?string $commentaire = null
    ): void {

        $email = (new Email())->from('noreply@tonapp.com')
                            ->to($researcherEmail)
                            ->subject('Décision finale concernant votre papier')
                            ->html(sprintf(
                                "<p>Bonjour,</p>
                                <p>Vous avez reçu l'avis final concernant le papier : <strong>%s</strong></p>
                                <p>Score : <strong>%s</strong></p>
                                %s",
                                $paperTitle,
                                $finalDecision,
                                $commentaire ? "<p>Commentaire : $commentaire</p>" : ""
                            ));

        $this->mailer->send($email);
            }
    }
