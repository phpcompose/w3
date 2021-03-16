<?php


namespace W3\Email;


use Compose\Container\ResolvableInterface;
use Mailgun\Mailgun;
use Mailgun\Model\Message\SendResponse;

class MailgunPlugin implements ResolvableInterface
{
    public function __invoke(Message $message, array $options)
    {
        static $mg = null;
        if(!$mg) {
            $apiKey = $options['apiKey'] ?? null;
            if(!$apiKey) {
                throw new \Exception("Mailgun API KEY is required.");
            }

            $mg = Mailgun::create($apiKey);
        }

        $host = $options['host'] ?? 'mg.spandexworld.com';

        try {
            /**
             * @var SendResponse
             */
            $result = $mg->messages()->send($host, [
                'from'    => $message->from,
                'to'      => Message::toAddressString($message->tos),
                'subject' => $message->subject,
                'html'    => $message->body
            ]);

            if($result) {
                return $result->getId();
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}