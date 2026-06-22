<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class TestWhatsappReminder extends Command
{
    protected $signature = 'wa:test {phone} {--message=}';
    protected $description = 'Send a test WhatsApp message or reminder';

    public function handle(WhatsappService $whatsapp): int
    {
        $phone = $this->argument('phone');

        if (!$whatsapp->isConnected()) {
            $this->error('WhatsApp service is not connected! Make sure node index.js is running.');
            return self::FAILURE;
        }

        $this->info('WhatsApp is connected.');

        $message = $this->option('message') ?: "✅ *تجربة من Booksy*\n\nهذه رسالة تجريبية للتأكد أن النظام يعمل بنجاح.\n\n💛 Booksy";

        $this->info("Sending to {$phone}...");
        $result = $whatsapp->send($phone, $message, null, null, 'test');

        if ($result) {
            $this->info('Message sent successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed to send message. Check whatsapp_logs table for details.');
        return self::FAILURE;
    }
}
