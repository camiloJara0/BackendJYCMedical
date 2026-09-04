<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudCitaRecibida extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;

    public function __construct($solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Nueva Solicitud de Cita de Mantenimiento',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'emails.solicitudCitaRecibida',
        );
    }

    public function attachments()
    {
        return [];
    }
}
