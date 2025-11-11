<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberPreRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Member $member) {}

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
            ->subject('New Member Pre-Registration Pending Review')
            ->line("{$this->member->full_name} {$this->member->family_name} just pre-registered.")
            ->line('Please review the details and complete any necessary approval steps.')
            ->action('View Member Profile', route('members.show', $this->member))
            ->line('Thank you for keeping member onboarding smooth.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'member_id' => $this->member->id,
            'full_name' => "{$this->member->full_name} {$this->member->family_name}",
            'status' => $this->member->status,
        ];
    }
}
