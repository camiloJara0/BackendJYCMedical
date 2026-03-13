<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CotizacionRecibida extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($cotizacion, $imagenFile = null)
    {
        $this->cotizacion = $cotizacion;
        $this->imagenFile = $imagenFile;

    }

    public function build()
    {
        $email = $this->view('emails.cotizacionRecibida')
                    ->with([
                        'cotizacion' => $this->cotizacion,
                        'imagenFile' => $this->imagenFile,
                        ]);

        if ($this->imagenFile) {
            $email->attachData(
                file_get_contents($this->imagenFile->getRealPath()),
                $this->imagenFile->getClientOriginalName(),
                [
                    'mime' => $this->imagenFile->getMimeType(),
                    'as' => $this->imagenFile->getClientOriginalName(),
                    'id' => 'imagenReferencia'
                ]
            );
        }

        return $email;
    }


}
