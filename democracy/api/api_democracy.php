<?php
class api_democracy extends \SYSTEM\API\api_system {
    
    private static function mailcannon($from,$subject,$html_file,$text_file,$to,$images,$replacements,$smtp){
        $bcc = null;
        $delay = 0;
        $silent = true;
        $unsubscribe_list = null;
        $attachments = [];
        \mailcannon::fire(  $bcc,
                            $delay,
                            $from,
                            $subject,
                            $html_file,
                            $text_file,
                            $to,
                            $unsubscribe_list,
                            $images,
                            $attachments,
                            $replacements,
                            $smtp,
                            $silent);
    }
    
    public static function call_send_mail($data){
        if(array_key_exists('files', $data)){
            $data['files'] = json_decode($data['files']);}
        $data_json = str_replace('\/', '/',json_encode($data,JSON_PRETTY_PRINT));
        //SendMail
        $from = 'Website | DEMOCRACY <'.(array_key_exists('email',$data) ? $data['email'] : 'contact@democracy-deutschland.de').'>';
        $to = 'contact@democracy-deutschland.de';
        //$to = 'ulf.gebhardt@webcraft-media.de';
        $subject = '📱 DEMOCRACY Website: '.((array_key_exists('type',$data) && array_key_exists('email',$data)) ? $data['type'].' from '.$data['email'] : 'EMail from democracy-deutschland.de');
        $html_file = (new \PAPI('tpl/send_mail.tpl'))->SERVERPATH();
        $text_file = (new \PAPI('tpl/send_mail.txt'))->SERVERPATH();
        $replacements = [   'data_json' =>  ['value' => ['text' => $data_json]],
                            'type'      =>  ['value' => ['text' => array_key_exists('type',$data) ? $data['type'] : 'No Type given']],
                            'email'     =>  ['value' => ['text' => array_key_exists('email',$data) ? $data['email'] : 'No EMail given']],
                            'name'      =>  ['value' => ['text' => array_key_exists('name',$data) ? $data['name'] : 
                                                                   (array_key_exists('vorname',$data) && array_key_exists('nachname',$data)) ?
                                                                    $data['vorname'].' '.$data['nachname'] : 'No Name given']],
                            'text'      =>  ['value' => ['text' => array_key_exists('text',$data) ? $data['text'] : 'No Text given']]];
        $images = ["democracy_logo" => (new \PAPI('img/logo.png'))->SERVERPATH()];
        $smtp = \SYSTEM\CONFIG\config::get(\config_ids::DEMOCRACY_EMAIL_CONTACT);
        self::mailcannon($from,$subject,$html_file,$text_file,$to,$images,$replacements,$smtp);
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    public static function call_send_subscribe($data){
        \SQL\SUBSCRIBE_ADD::Q1(array($data['email']));
            
        $sub = \SQL\SUBSCRIBE_GET::Q1(array($data['email']));
        if(!$sub['confirmed']){
            self::send_subscribe_mail($data['email']);}
            
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    /**
     * @see http://www.jwz.org/doc/mid.html
     */
    public static function generateMessageID()
    {
        return sprintf(
            "<%s.%s@%s>",
            base_convert(microtime(), 10, 36),
            base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
            "democracy-deutschland.de"
        );
    }
    
    private static function send_subscribe_mail($email){
        require((new \SYSTEM\PROOT('PHPMailer-master/PHPMailerAutoload.php'))->SERVERPATH());
        date_default_timezone_set('Europe/Berlin');

        $mail = new PHPMailer;
        
        $mail->CharSet = 'utf-8';  
 
        $mail->Host = 'atmanspacher.eu';
        $mail->Port = 465;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
   
        $mail->setFrom(     'contact@democracy-deutschland.de', 'DEMOCRACY Deutschland e.V.');
        $mail->addReplyTo(  'contact@democracy-deutschland.de', 'DEMOCRACY Deutschland e.V.');
        $mail->addAddress(  $email);
        
        $mail->addCustomHeader('Return-Path', 'contact@democracy-deutschland.de');
        $mail->addCustomHeader('Message-ID', self::generateMessageID());
        $mail->addCustomHeader('Date', date('r', time()));
        
        $token = \SYSTEM\TOKEN\token::request('token_confirm_subscribe', array('email' => $email));
        
        $html = \SYSTEM\PAGE\replace::replaceFile((new PAPI('tpl/send_mail_subscribe.tpl'))->SERVERPATH(), array('token' => $token));
        
        $mail->Subject = '📱 DEMOCRACY: Bitte bestätige Deine Newsletter-Anmeldung';
        $mail->Body = $html;
        $mail->IsHTML(true);

	//send the message, check for errors
	if(!$mail->send()){
	    throw new \SYSTEM\LOG\ERROR("Mailer Error: " . $mail->ErrorInfo);}
        
        \SQL\SUBSCRIBE_EMAIL_COUNT::Q1(array($email));
    }
    
    public static function call_beta($ios,$android,$email,$code){
        $code_valid = self::validate_code($code);
        
        if($code_valid){
            $data = \SQL\BETA_EMAIL_FIND::Q1(array($email));
            if(!$data){
                \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
            } else {
                if(!self::validate_code($data['code'])){
                    \SQL\BETA_DELETE::QI(array($email));
                    \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
                } else {
                    throw new ERROR('This EMail has already redeemed a Code');}
            }
        } else {
            \SQL\BETA_INSERT::QI(array($code,$email,$android,$ios));
        }
        
        //SendMail
        $bcc = null;
        $delay = 0;
        $from = 'Prototyp | DEMOCRACY <prototyping@democracy-deutschland.de>';
        $subject = '📱 DEMOCRACY: Deine Prototyp Bewerbung ist eingegangen!';
        $html_file = (new \PAPI('tpl/send_mail_beta.tpl'))->SERVERPATH();
        $text_file = (new \PAPI('tpl/send_mail_beta.txt'))->SERVERPATH();
        $to = $email;
        $unsubscribe_list = null;
        $images = ["democracy_logo" => (new \PAPI('img/logo.png'))->SERVERPATH()];
        $attachments = [];
        $replacements = [];
        $smtp = \SYSTEM\CONFIG\config::get(\config_ids::DEMOCRACY_EMAIL_PROTOTYPING);
        $silent = true;
        \mailcannon::fire($bcc, $delay, $from, $subject, $html_file, $text_file, $to, $unsubscribe_list, $images, $attachments, $replacements,$smtp, $silent);
        
        return \SYSTEM\LOG\JsonResult::ok();
    }
    
    public static function validate_code($code){
        return \SQL\BETA_CODE_VALIDATE::Q1(array($code))['count'] !== 0 ? true: false;
    }
    
    public static function call_upload(){
        $file_name = md5_file($_FILES['datei']['tmp_name']).'_'.basename($_FILES['datei']['name']);
        if(!\SYSTEM\FILES\files::put('upload', $file_name , $_FILES['datei']['tmp_name'])){
            throw new \SYSTEM\LOG\ERROR("Upload Problem");}
        
        return \SYSTEM\LOG\JsonResult::toString(['file_name' => $file_name]);
    }
}