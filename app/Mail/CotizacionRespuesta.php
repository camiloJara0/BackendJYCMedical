<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CotizacionRespuesta extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($cotizacion, $respuesta, $archivo = null)
    {
        $this->cotizacion = $cotizacion;
        $this->respuesta = $respuesta;
        $this->archivo = $archivo;
    }

    public function build()
    {
        $email = $this->view('emails.cotizacionRespuesta')
                    ->with([
                        'cotizacion' => $this->cotizacion,
                        'respuesta' => $this->respuesta,
                        'archivo' => $this->archivo,
                        ]);

        if ($this->archivo) {
            $email->attachData(
                file_get_contents($this->archivo->getRealPath()),
                $this->archivo->getClientOriginalName(),
                [
                    'mime' => $this->archivo->getMimeType(),
                    'as' => $this->archivo->getClientOriginalName(),
                ]
            );
        }

        return $email;
    }

}
