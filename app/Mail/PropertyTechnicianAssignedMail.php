<?php

namespace App\Mail;

use App\Models\MaintenanceProvider;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PropertyTechnicianAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public MaintenanceProvider $technician,
        public Collection $tickets,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Propiedad asignada con tickets pendientes: '.$this->property->internal_name,
        );
    }

    public function content(): Content
    {
        $appName = (string) config('app.name', 'Naboo');
        $isSuHomes = Str::contains(Str::lower($appName), 'suhomes');

        return new Content(
            view: 'emails.property-technician-assigned',
            with: [
                'property' => $this->property,
                'technician' => $this->technician,
                'tickets' => $this->tickets,
                'maintenanceUrl' => route('maintenance.index', ['property' => $this->property->uuid]),
                'loginUrl' => url('/login'),
                'logoUrl' => asset($isSuHomes ? 'assets/img/suhomes-app-logo.png' : 'assets/img/naboo-logo.png'),
                'appName' => $appName,
            ],
        );
    }
}
