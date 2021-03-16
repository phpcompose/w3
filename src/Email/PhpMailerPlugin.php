<?php


namespace W3\Email;


use Compose\Container\ResolvableInterface;

class PhpMailerPlugin implements ResolvableInterface
{
    /**
     * @param Message $message
     * @param array $options
     * @return bool
     * @throws \phpmailerException
     */
    public function __invoke(Message $message, array $options)
    {
        $mailer = new \PHPMailer();
        if(isset($options['smtp']) && $options['smtp']) {
            $mailer->isSMTP();
            $mailer->Host = $options['host'] ?? null;
            $mailer->SMTPAuth = true;
            $mailer->SMTPSecure = 'tls';
            $mailer->Port = $options['port'] ?? null;
            $mailer->Username = $options['username'] ?? null;
            $mailer->Password = $options['password'] ?? null;
        }

        $mailer->isHTML($message->html);
        $mailer->setFrom($message->fromEmail, $message->fromName);
        $mailer->addReplyTo($message->fromEmail, $message->fromName);
        $mailer->Subject = $message->subject;
        if($message->body) {
            $mailer->Body = $message->body;
        }

        foreach($message->tos as $email => $name) {
            $mailer->addAddress($email, $name);
        }

        foreach ($message->ccs as $email => $name) {
            $mailer->addCC($email, $name);
        }

        foreach($message->bccs as $email => $name) {
            $mailer->addBCC($email, $name);
        }

        return $mailer->send();
    }
}