<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $user)
    {
        $this->event = $event;
        $this->user  = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DANAEventpark] Sự kiện "' . $this->event->title . '" đã bị hủy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.event-cancelled',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
