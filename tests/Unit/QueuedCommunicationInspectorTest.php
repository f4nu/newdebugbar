<?php

use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use NewDebugBar\Support\QueuedCommunicationInspector;
use NewDebugBar\Tests\Fixtures\Mail\ProfiledMailable;
use NewDebugBar\Tests\Fixtures\Notifications\ProfiledNotifiable;
use NewDebugBar\Tests\Fixtures\Notifications\ProfiledNotification;

it('identifies queued mail and notifications without rendering the message', function (): void {
    $inspector = new QueuedCommunicationInspector;
    $mailable = (new ProfiledMailable(
        subjectLine: 'Queued subject',
        heading: 'Queued heading',
        messageCopy: 'Queued body',
    ))->to('recipient@example.test');
    $mail = $inspector->inspect(new SendQueuedMailable($mailable));
    $notification = $inspector->inspect(new SendQueuedNotifications(
        collect([new ProfiledNotifiable('private@example.test')]),
        new ProfiledNotification('private payload'),
        ['mail'],
    ));

    expect($mail)
        ->communication_type->toBe('mail')
        ->communication_class->toBe(ProfiledMailable::class)
        ->recipient_count->toBe(1)
        ->and($notification)
        ->communication_type->toBe('notification')
        ->communication_class->toBe(ProfiledNotification::class)
        ->channels->toBe(['mail'])
        ->notifiable_types->toBe([ProfiledNotifiable::class])
        ->notifiable_count->toBe(1);
});
