<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SolicitudCitaAtendida extends Mailable
{
    use Queueable, SerializesModels;

    public $solicitud;
    public $respuesta;
    public $archivoPath;

    public function __construct($solicitud, $respuesta, $archivoPath = null)
    {
        $this->solicitud = $solicitud;
        $this->respuesta = $respuesta;
        $this->archivoPath = $archivoPath;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Respuesta a su Solicitud de Cita - J&C Medical',
        );
    }

    public function content()
    {
        return new Content(
            html: 'emails.solicitudCitaAtendida',
            with: [
                'solicitud' => $this->solicitud,
                'respuesta' => $this->respuesta,
            ],
        );
    }

    public function attachments()
    {
        if ($this->archivoPath && file_exists($this->archivoPath)) {
            return [
                Attachment::fromPath($this->archivoPath)
                    ->as(basename($this->archivoPath))
                    ->withMime(mime_content_type($this->archivoPath)),
            ];
        }

        return [];
    }
}
