<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
        $this->from = config('services.twilio.phone_number');
    }

    /**
     * Send SMS using Twilio
     *
     * @param string $to
     * @param string $message
     * @return \Twilio\Rest\Api\V2010\Account\MessageInstance
     */
    public function sendSms(string $to, string $message)
    {
        return $this->client->messages->create(
            $to, // To phone number
            [
                'from' => $this->from, // Twilio phone number
                'body' => $message, // Message content
            ]
        );
    }
}
