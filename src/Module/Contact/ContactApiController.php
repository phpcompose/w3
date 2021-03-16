<?php


namespace W3\Module\Contact;


use Compose\Container\ResolvableInterface;
use Compose\Http\Session;
use Compose\Mvc\Controller;
use Psr\Http\Message\ServerRequestInterface;

class ContactApiController extends Controller implements ResolvableInterface
{
    protected
        $fieldMap = [
            'fContactName' => 'name',
            'fContactEmail' => 'email',
            'fContactMessage' => 'message',
            'fContactSubject' => 'subject'
        ],

        /**
         * @var ContactService
         */
        $contactService;

    /**
     * ContactApiController constructor.
     * @param ContactService $contactService
     */
    public function __construct(ContactService $contactService, Session $session)
    {
        $this->contactService = $contactService;
        $this->csrf = new \W3\Support\Util\CsrfToken($session);
    }

    /**
     * @param ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function doIndex(ServerRequestInterface $request)
    {
        return $this->json(['some' => 'thing']);
    }

    /**
     * @param ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function doPost(ServerRequestInterface $request)
    {
        $values = $request->getParsedBody();

        // token check
        if(!$this->csrf->checkToken($values['_ctoken'] ?? '', 'fcontact')) {
            throw new \Compose\Http\HttpException('Invalid request sent.', 403);
        }

        $mappedValues = $this->map($values);
        $errors = $this->contactService->validate($mappedValues);
        if(!$errors) {
            $emailed = $this->contactService->sendEmail($mappedValues);
            if(!$emailed) {
                $errors = ['unable to send email.'];
            }
        } else {
            $errors = $this->map($errors, true);
        }

        return $this->json(['values' => $values, 'errors' => $errors, 'mapped' => $mappedValues]);
    }

    /**
     * @param array $values
     * @return array
     */
    protected function map(array $values, bool $reverse = false) : array
    {
        if($reverse) {
            $fields = array_flip($this->fieldMap);
        } else {
            $fields = $this->fieldMap;
        }

        $mapped = [];
        foreach($values as $key => $value) {
            $map = $fields[$key] ?? null;
            if($map) {
                $mapped[$map] = $value;
            }
        }

        return  $mapped;
    }

}