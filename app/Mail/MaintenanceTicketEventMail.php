<?php

namespace App\Mail;

use App\Models\MaintenanceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceTicketEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MaintenanceTicket $ticket,
        public string $event,
        public string $subjectLine,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-ticket-event',
            with: [
                'ticket' => $this->ticket,
                'event' => $this->event,
                'ticketUrl' => route('maintenance.show', $this->ticket),
                'loginUrl' => url('/login'),
                'logoUrl' => asset('assets/img/suhomes-app-logo.png'),
                'appName' => 'SuHomes',
            ],
        );
    }
}
