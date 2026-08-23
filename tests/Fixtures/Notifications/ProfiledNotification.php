<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProfiledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<string> $channels */
    public function __construct(
        public string $privateValue,
        public array $channels = ['mail'],
        public string $subjectLine = 'Your Kyoto journey is ready',
    ) {}

    /** @return list<string> */
    public function via(ProfiledNotifiable $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(ProfiledNotifiable $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Your journey is ready.')
            ->line('Review the Kyoto itinerary and confirm the final details.')
            ->action('Review journey', 'https://example.test/trips/kyoto-autumn');
    }
}
