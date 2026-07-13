<?php

namespace App\Mail;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TRIGGER_UPCOMING = 'upcoming';
    public const TRIGGER_OVERDUE = 'overdue';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Expense $expense,
        public string $trigger,
        public int $daysBefore,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjectPrefix = $this->trigger === self::TRIGGER_OVERDUE
            ? 'Gasto vencido'
            : 'Gasto próximo a vencer';

        return new Envelope(
            subject: $subjectPrefix . ' - ' . $this->expense->concept,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.expense-notification',
            with: [
                'expense' => $this->expense,
                'trigger' => $this->trigger,
                'daysBefore' => $this->daysBefore,
            ],
        );
    }
}
