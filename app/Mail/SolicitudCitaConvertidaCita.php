<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudCitaConvertidaCita extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $cita;

    public function __construct($solicitud, $cita)
    {
        $this->solicitud = $solicitud;
        $this->cita = $cita;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Su Solicitud fue Agendada - J&C Medical',
        );
    }

    public function content()
    {
        return new Content(
            html: 'emails.solicitudCitaConvertidaCita',
            with: [
                'solicitud' => $this->solicitud,
                'cita' => $this->cita,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
