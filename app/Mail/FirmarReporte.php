<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FirmarReporte extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reporte, $url)
    {
        $this->reporte = $reporte;
        $this->url = $url;
    }

    public function build()
    {
        $email = $this->view('emails.firmarReporte')
                    ->with([
                        'reporte' => $this->reporte,
                        'url' => $this->url,
                        ]);

        return $email;
    }
}
