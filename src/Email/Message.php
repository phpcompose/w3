<?php


namespace W3\Email;



/**
 * Class EmailerMessage
 * @package App\Support
 */
class Message
{
    public $subject = null;
    public $body = null;
    public $text = null;
    public $tos = [];
    public $bccs = [];
    public $ccs = [];
    public $fromEmail = null;
    public $fromName = null;
    public $html = true;

    /**
     * EmailerMessage constructor.
     * @param string|null $subject
     * @param string|null $message
     */
    public function __construct(string $subject = null, string $body = null)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function setFrom(string $email, string $name = null)
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function addTo(string $email, string $name = null)
    {
        $this->tos[$email] = $name;
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function addBcc(string $email, string $name = null)
    {
        $this->bccs[$email] = $name;
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function addCc(string $email, string $name = null)
    {
        $this->ccs[$email] = $name;
    }

    /**
     * @param array $adresses
     * @return string
     */
    public static function toAddressString(array $adresses) : string
    {
        $arr = [];
        foreach ($adresses as $email => $name) {
            if($name) {
                $arr[] = "{$name} <{$email}>";
            } else {
                $arr[] = $email;
            }
        }

        if(empty($arr)) {
            return '';
        }

        return implode(',', $arr);
    }
}