<?php

namespace App\Notifications;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contribution $contribution
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
        return (new MailMessage)
            ->subject('New Contribution Submitted - MCDF Community Fund')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('A new contribution has been submitted and requires verification.')
            ->line('**Member:** '.$this->contribution->member->full_name)
            ->line('**Amount:** ₦'.number_format($this->contribution->amount, 2))
            ->line('**Payment Method:** '.$this->contribution->payment_method_label)
            ->line('**Receipt Number:** '.$this->contribution->receipt_number)
            ->line('**Submitted:** '.$this->contribution->created_at->format('M d, Y \a\t g:i A'))
            ->action('Verify Contribution', route('contributions.verify'))
            ->line('Please review the uploaded receipt and verify the payment.')
            ->line('Thank you for using MCDF Community Fund Initiative!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contribution_submitted',
            'contribution_id' => $this->contribution->id,
            'member_name' => $this->contribution->member->full_name,
            'member_registration' => $this->contribution->member->registration_no,
            'amount' => $this->contribution->amount,
            'payment_method' => $this->contribution->payment_method_label,
            'receipt_number' => $this->contribution->receipt_number,
            'submitted_at' => $this->contribution->created_at->toISOString(),
            'verification_url' => route('contributions.verify'),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'contribution_submitted';
    }
}
