<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    protected function send_email($email) {
        $json = json_encode(array(
            'From' => $email['from'],
            'To' => $email['to'],
            //'Cc' => $email['cc'],
            //'Bcc' => $email['bcc'],
            'Subject' => $email['subject'],
            //'Tag' => $email['tag'],
            'HtmlBody' => $email['html_body'],
            //'TextBody' => $email['text_body'],
            'ReplyTo' => $email['reply_to'],
            //'Headers' => $email['headers'],
            'Attachments' => isset($email['attachments']) ? $email['attachments'] : null
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.postmarkapp.com/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-Server-Token: '. POSTMARKKEY,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
}