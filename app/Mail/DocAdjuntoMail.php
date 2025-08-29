<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocAdjuntoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $asunto,
        public string $mensajePlano,
        public string $rutaAdjunto,
        public string $nombreAdjunto
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->asunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plano', with: [
            'mensaje' => $this->mensajePlano,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->rutaAdjunto)
                ->as($this->nombreAdjunto)
                ->withMime('application/octet-stream'),
        ];
    }
}
