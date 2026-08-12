<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberVerificationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Member $member)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! Your Matrimonial Profile Bio-Data Has Been Verified')
            ->greeting("Hello {$this->member->first_name},")
            ->line("Your matrimonial bio-data profile ({$this->member->member_code}) has been reviewed and successfully VERIFIED by our community counselor team.")
            ->line('Your profile now displays a Verified Identity Badge, increasing member trust and response rates.')
            ->action('View Your Verified Profile', url('/matrimonial/directory'))
            ->line('Thank you for using our Matrimonial & Community Directory Platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'member_id' => $this->member->id,
            'member_code' => $this->member->member_code,
            'status' => 'Verified',
            'message' => "Bio-data profile {$this->member->member_code} verified.",
        ];
    }
}
