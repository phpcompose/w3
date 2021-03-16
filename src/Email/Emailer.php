<?php


namespace W3\Email;

use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;

/**
 * Class Emailer
 * @package App\Support
 */
class Emailer implements ServiceFactoryInterface
{
    /** @var callable */
    protected $plugin;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Emailer constructor.
     * @param callable|null $plugin
     * @param array $options
     */
    public function __construct(callable $plugin = null, array $options = [])
    {
        if(!$plugin) {
            $plugin = [$this, 'sendmail'];
        }

        $this->setPlugin($plugin, $options);
    }

    /**
     * @param ContainerInterface $container
     * @param string $name
     * @return mixed|void
     */
    public static function create(ContainerInterface $container, string $name)
    {
        $config = $container->get(Configuration::class);
        $plugin = $config['emailer']['plugin'] ?? null;
        $options = $config['emailer']['options'] ?? [];

        if($plugin) {
            $plugin = $container->get($plugin);
        }

        return new self($plugin, $options);
    }

    /**
     * @param string|null $subject
     * @param string|null $message
     * @return Message
     */
    public function createMessage(string $subject = null, string $message = null) : Message
    {
        return new Message($subject, $message);
    }

    /**
     * @param callable $plugin
     */
    public function setPlugin(callable $plugin, array $options = [])
    {
        $this->plugin = $plugin;
        $this->options = $options;
    }

    /**
     * @param Message $message
     * @return bool
     * @throws \Exception
     */
    public function send(Message $message) : bool
    {
        $sender = $this->plugin ?? [$this, 'sendmail'];

        try {
            return $sender($message, $this->options);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array $tos
     * @param string $from
     * @return mixed
     */
    public function mail(string $subject, string $message, array $tos, string $from)
    {
        $msg = $this->createMessage($subject, $message);
        $msg->setFrom($from);
        foreach($tos as $name => $email) {
            if(is_int($name)) {
                $msg->addTo($email);
            } else {
                $msg->addTo($email, $name);
            }
        }

        return $msg->send($msg);
    }

    /**
     * @param Message $message
     * @param array $options
     * @return bool
     */
    protected function sendmail(Message $message, array $options) : bool
    {
        $to = Message::toAddressString($message->tos);
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=iso-8859-1',
            'From : ' . $message->from
        ];

        if($message->bccs) {
            $headers[] = 'Bcc: ' . Message::toAddressString($message->bccs);
        }

        if($message->ccs) {
            $headers[] = 'Cc: ' . Message::toAddressString($message->ccs);
        }

        return mail($to, $message->subject, $message->body, implode("\r\n", $headers));
    }
}