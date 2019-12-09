<?php
/**
 * phpmailer support
 *
 */
class EM_Mailer {
	
	/**
	 * if any errors crop up, here they are
	 * @var array
	 */
	public $errors = array();
	
	/**
	 * Send an email via the EM-saved settings.
	 * @param $subject
	 * @param $body
	 * @param $receiver
	 * @param $attachments
	 * @return boolean
	 */
	public function send($subject="no title",$body="No message specified", $receiver='', $attachments = array() ) {
		//TODO add an EM_Error global object, for this sort of error reporting. (@marcus like StatusNotice)
		$subject = html_entity_decode(wp_kses_data($subject)); //decode entities, but run kses first just in case users use placeholders containing html
		if( is_array($receiver) ){
			$receiver_emails = array();
			foreach($receiver as $receiver_email){
				$receiver_emails[] = is_email($receiver_email);
			}
			$emails_ok = !in_array(false, $receiver_emails);
		}else{
			$emails_ok = is_email($receiver);
		}
		if( get_option('dbem_smtp_html') && get_option('dbem_smtp_html_br') ){
			$body = nl2br($body);
		}
		if ( $emails_ok && get_option('dbem_rsvp_mail_send_method') == 'wp_mail' ){
			$from = get_option('dbem_mail_sender_address');
			$headers = get_option('dbem_mail_sender_name') ? 'From: '.get_option('dbem_mail_sender_name').' <'.$from.'>':'From: '.$from;
			if( get_option('dbem_smtp_html') ){ //create filter to change content type to html in wp_mail
				add_filter('wp_mail_content_type','EM_Mailer::return_texthtml');
			}
			//prep attachments for WP Mail, which only accept a path
			$wp_mail_attachments = array();
			foreach( $attachments as $attachment ){
				$wp_mail_attachments[] = $attachment['path'];
			}
			//send and handle errors
			$send = wp_mail($receiver, $subject, $body, $headers, $wp_mail_attachments);
			if(!$send){
				global $phpmailer;
				$this->errors[] = $phpmailer->ErrorInfo;
			}
			return $send;
		}elseif( $emails_ok ){
			$this->load_phpmailer();
			$mail = new PHPMailer();
			try{
				//$mail->SMTPDebug = true;
				if( get_option('dbem_smtp_html') ){
					$mail->isHTML();
				}
				$mail->ClearAllRecipients();
				$mail->ClearAddresses();
				$mail->ClearAttachments();
				$mail->CharSet = 'utf-8';
			    $mail->SetLanguage('en', dirname(__FILE__).'/');
				$mail->PluginDir = dirname(__FILE__).'/phpmailer/';
				$host = get_option('dbem_smtp_host');
				//if port is supplied via the host address, give that precedence over the port setting
				if( preg_match('/^(.+):([0-9]+)$/', $host, $host_port_matches) ){
					$mail->Host = $host_port_matches[1];
					$mail->Port = $host_port_matches[2];
				}else{
					$mail->Host = $host;
					$mail->Port = get_option('dbem_rsvp_mail_port');
				}
				$mail->Username = get_option('dbem_smtp_username');
				$mail->Password = get_option('dbem_smtp_password');
				$mail->From = get_option('dbem_mail_sender_address');
				$mail->FromName = get_option('dbem_mail_sender_name'); // This is the from name in the email, you can put anything you like here
				$mail->Body = $body;
				$mail->Subject = $subject;
				//SSL/TLS
				if( get_option('dbem_smtp_encryption') ){
					$mail->SMTPSecure = get_option('dbem_smtp_encryption');
				}
				$mail->SMTPAutoTLS = get_option('dbem_smtp_autotls') == 1;
				//add attachments
				if( is_array($attachments) ){
					foreach($attachments as $attachment){
					    $att = array('name'=> '', 'encoding' => 'base64', 'type' => 'application/octet-stream');
					    if( is_array($attachment) ){
					        $att = array_merge($att, $attachment);
					    }else{
					        $att['path'] = $attachment;
					    }
					    $mail->AddAttachment($att['path'], $att['name'], $att['encoding'], $att['type']);
					}
				}
				if(is_array($receiver)){
					foreach($receiver as $receiver_email){
						$mail->AddAddress($receiver_email);
					}
				}else{
					$mail->AddAddress($receiver);
				}
				do_action('em_mailer', $mail); //$mail will still be modified
				
				//Protocols
			    if( get_option('dbem_rsvp_mail_send_method') == 'qmail' ){
					$mail->isQmail();
				}elseif( get_option('dbem_rsvp_mail_send_method') == 'sendmail' ){
					$mail->isSendmail();
				}else {
					$mail->Mailer = get_option('dbem_rsvp_mail_send_method');
				}
				if(get_option('dbem_rsvp_mail_SMTPAuth') == '1'){
					$mail->SMTPAuth = TRUE;
			    }
				do_action('em_mailer_before_send', $mail, $subject, $body, $receiver, $attachments); //$mail can still be modified
			    $send = $mail->Send();
				if(!$send){
					$this->errors[] = $mail->ErrorInfo;
				}
				do_action('em_mailer_sent', $mail, $send); //$mail can still be modified
				return $send;
			}catch( phpmailerException $ex ){
				$this->errors[] = $mail->ErrorInfo;
				return false;
			}
		}else{
			$this->errors[] = __('Please supply a valid email format.', 'events-manager');
			return false;
		}
	}
	
	/**
	 * load phpmailer classes
	 */
	public function load_phpmailer(){
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
	}
	
	public static function return_texthtml(){
		return "text/html";
	}
}
?>