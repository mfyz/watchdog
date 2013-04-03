<?php

// NOTE:  This is a copy of mail3.php in moonit2.
//        If you change this file, please change its copy.

class email {
    public $from;
    public $fromName;
    public $to;
    public $subject;
    public $message;
    public $text;
    public $body;
    public $headers;
    public $headersArray;
    public $templatePath;

    function __construct($subject = FALSE, $message = FALSE, 
        $extra_headers = FALSE) {

        if ($subject) $this->setSubject($subject);
        if ($message) $this->setMessage($message);

        if ($extra_headers) {
            $this->addHeader(implode("\n", $extra_headers));
        }
    }

    function setSubject($subject){
        $this->subject = $subject;
    }

    function setMessage($html, $text = false){
        if( !$text ) $text = '"This email requires HTML to view. Please open this email in an application that allows you to view HTML emails.';

        $this->message = $html;
        $this->text    = $text;
    }

    function setFrom($email, $name = false){
        if( !$name ) $name = 'Moonit.com'; // default from name
        $this->from = $email;
        $this->fromName = $name;
    }

    function addHeader($headerName, $headerValue = FALSE){
        $this->headers .= $headerName . ($headerValue ? ':' . $headerValue : '') . "\n";
    }

    function generateBody($message, $text = false, $addHeaders = true){
        if( $addHeaders ){
            $this->addHeader('MIME-Version: 1.0');
            $this->addHeader('Content-Type: multipart/alternative;');
            $this->addHeader('boundary="----=_NextPart_000_nIFR_o2AvuiKMT7.5XzKIHuFTz"');
        }

        return <<<MAIL_BODY
This is a multi-part message in MIME format.

------=_NextPart_000_nIFR_o2AvuiKMT7.5XzKIHuFTz
Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

$text

------=_NextPart_000_nIFR_o2AvuiKMT7.5XzKIHuFTz
Content-Type: text/html; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

$message

------=_NextPart_000_nIFR_o2AvuiKMT7.5XzKIHuFTz--
MAIL_BODY;
    }

    function send($to, $to_name = NULL, $use_sendgrid = TRUE) {
	    $fromHeader = 'From: ' . $this->fromName . ' <'. $this->from . '>';
	    $this->addHeader($fromHeader);

	    // Generate email body.
	    //$this->body = $this->generateBody($this->message, $this->text);

	    $this->addHeader('MIME-Version: 1.0');
	    $this->addHeader('Content-Type: text/html; charset=ISO-8859-1');
	    $this->body = $this->message;

	    // Send mail.
	    return mail($to, $this->subject, $this->body, $this->headers);
    }

    function setTemplate($templatePath){
        $this->template = $this->templatePath .'/'. $templatePath;
    }

    function setTemplateAbsolute($templatePath) {
        $this->template = $templatePath;
    }

    function renderTemplate($data = array()){
        $tpl = new template($this->template);
        $tpl->data($data);
	    $html = $tpl->render();
        $this->message = $html;
	    $this->text = preg_replace("/\n\n+/", "\n\n", strip_tags($html));
    }
}
