<?php


namespace W3\Module\Contact;

require_once 'public/library/securimage/securimage.php';

use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Exception;
use W3\Email\Emailer;
use W3\Validation;
use Securimage;


class ContactService implements ResolvableInterface
{
    protected
        /**
         * @var Emailer
         */
        $emailer,

        /**
         * @var array
         */
        $emails,

        /**
         * @var array
         */
        $subjects;

    /**
     * ContactService constructor.
     * @param Configuration $config
     * @param Emailer $emailer
     */
    public function __construct(Configuration $config, Emailer $emailer)
    {
        $this->emails = $config['modules']['contact']['emails'] ?? [];
        $this->subjects = $config['modules']['contact']['subjects'] ?? [];
//        $this->fields = $config->readConfig($config['modules']['contact']['fields_config']);

        $this->emailer =$emailer;
    }

    /**
     * @return array
     */
    public function getAvailableEmails() : array
    {
        return $this->emails;
    }

    /**
     * @return array
     */
    public function getAvailableSubjects() : array
    {
        return $this->subjects;
    }

    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * @param array $values
     * @return array|null
     */
    public function validate(array &$values) : ?array
    {
        $processor = new Validation\Processor();
        $processor->setRequiredValues(['name', 'email', 'subject', 'message']);
        $processor->addFilterer(['name', 'subject', 'phone'],
            new Validation\FilterInputFilterer(FILTER_SANITIZE_STRING));

        $processor->addValidator('name',
            new Validation\FilterInputValidator(FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $processor->addValidator(' email',
            new Validation\FilterInputValidator(FILTER_VALIDATE_EMAIL));
        $processor->addValidator('message',
            new Validation\FilterInputValidator(FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES));

        // captcha validator
        $processor->addValidator('captcha', function($val) {

            $secuimage = new Securimage();

            $result = $secuimage->check($val);
            if(!$result) {
                return 'Code does not match.';
            }
        });

        $errors = $processor->process($values);
        if(empty($errors)) return null;
        return $errors;
    }

    /**
     * @param array $values
     * @return bool
     * @throws Exception
     */
    public function sendEmail(array $values) : bool
    {
        $sub = $values['subject'];
        $subject = $this->subjects[$sub] ?? null;
        if(!$subject) {
            throw new Exception("Invalid request");
        }
        $emails = $this->emails[$sub];
        $tos = (array) ($emails['to']);
        $bccs = (array) ($emails['bcc'] ?? []);
        $ccs = (array) ($emails['cc'] ?? []);
        $message =  $this->toEmailMessage($values['message']);
        $email = $this->emailer->createMessage($subject);
        $email->body = $message;
        $email->html = false;
        $email->setFrom($values['email'], $values['name']);
        foreach($tos as $to) {
            $email->addTo($to);
        }

        foreach($bccs as $bcc) {
            $email->addBcc($bcc);
        }

        foreach ($ccs as $cc) {
            $email->addCc($cc);
        }

        if($this->emailer->send($email)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $message
     * @return string
     */
    protected function toEmailMessage(string $message) : string
    {
        return <<<EOF
{$message}

========================================
Spandex World Contact Form                
EOF;
    }
}