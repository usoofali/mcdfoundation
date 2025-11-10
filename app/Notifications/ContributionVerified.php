<?php

namespace App\Notifications;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contribution $contribution,
        public bool $approved,
        public ?string $notes = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->approved ? 'approved' : 'rejected';
        $statusColor = $this->approved ? 'success' : 'error';

        $mailMessage = (new MailMessage)
            ->subject('Contribution '.ucfirst($status).' - MCDF Community Fund')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your contribution has been '.$status.' by our staff.')
            ->line('**Receipt Number:** '.$this->contribution->receipt_number)
            ->line('**Amount:** ₦'.number_format($this->contribution->amount, 2))
            ->line('**Payment Method:** '.$this->contribution->payment_method_label)
            ->line('**Verified:** '.$this->contribution->verified_at->format('M d, Y \a\t g:i A'));

        if ($this->approved) {
            $mailMessage->line('✅ **Status:** Approved - Your contribution has been added to your account.')
                ->action('View My Contributions', route('contributions.index'));
        } else {
            $mailMessage->line('❌ **Status:** Rejected - Please contact support for more information.')
                ->action('Submit New Contribution', route('contributions.submit'));
        }

        if ($this->notes) {
            $mailMessage->line('**Verification Notes:** '.$this->notes);
        }

        return $mailMessage->line('Thank you for using MCDF Community Fund Initiative!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contribution_verified',
            'contribution_id' => $this->contribution->id,
            'receipt_number' => $this->contribution->receipt_number,
            'amount' => $this->contribution->amount,
            'payment_method' => $this->contribution->payment_method_label,
            'approved' => $this->approved,
            'status' => $this->approved ? 'approved' : 'rejected',
            'verified_at' => $this->contribution->verified_at->toISOString(),
            'verifier_name' => $this->contribution->verifier->name ?? 'Staff',
            'notes' => $this->notes,
            'contributions_url' => route('contributions.index'),
            'submit_url' => route('contributions.submit'),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'contribution_verified';
    }
}
