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
	 * Array of attachments which will be added to WP_Mail's phpmailer just before sending, and subsequently emptied.
	 * @var array
	 */
	public static $attachments = array();
	
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
			$headers = array();
			$headers[] = get_option('dbem_mail_sender_name') ? 'From: '.get_option('dbem_mail_sender_name').' <'.$from.'>':'From: '.$from;
			$headers[] = get_option('dbem_mail_sender_name') ? 'Reply-To: '.get_option('dbem_mail_sender_name').' <'.$from.'>':'From: '.$from;
			if( get_option('dbem_smtp_html') ){ //create filter to change content type to html in wp_mail
				add_filter('wp_mail_content_type','EM_Mailer::return_texthtml');
			}
			//prep attachments for WP Mail, which only accept a path
			self::$attachments = $attachments;
			add_action('phpmailer_init', 'EM_Mailer::add_attachments_to_mailer', 9999, 1);
			//send and handle errors
			$send = wp_mail($receiver, $subject, $body, $headers);
			//unload attachments hook
			remove_action('phpmailer_init', 'EM_Mailer::add_attachments_to_mailer', 9999);
			//send email
			if(!$send){
				global $phpmailer;
				$this->errors[] = $phpmailer->ErrorInfo;
			}
			//cleanup
			self::delete_email_attachments($attachments);
			return $send;
		}elseif( $emails_ok ){
			$this->load_phpmailer();
			$mail = new PHPMailer();
			try{
				//$mail->SMTPDebug = true;
				if( get_option('dbem_smtp_html') ){
					$mail->isHTML();
				}
				$mail->clearAllRecipients();
				$mail->clearAddresses();
				$mail->clearAttachments();
				$mail->clearCustomHeaders();
				$mail->clearReplyTos();
				$mail->CharSet = 'utf-8';
			    $mail->setLanguage('en', dirname(__FILE__).'/');
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
				self::add_attachments_to_mailer($mail, $attachments);
				if(is_array($receiver)){
					foreach($receiver as $receiver_email){
						$mail->addAddress($receiver_email);
					}
				}else{
					$mail->addAddress($receiver);
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
			    $send = $mail->send();
				if(!$send){
					$this->errors[] = $mail->ErrorInfo;
				}
				do_action('em_mailer_sent', $mail, $send); //$mail can still be modified
				self::delete_email_attachments($attachments);
				return $send;
			}catch( phpmailerException $ex ){
				$this->errors[] = $mail->ErrorInfo;
				self::delete_email_attachments($attachments);
				return false;
			}
		}else{
			$this->errors[] = __('Please supply a valid email format.', 'events-manager');
			self::delete_email_attachments($attachments);
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
	
	/**
	 * Shorthand function for filters to return 'text/html' string.
	 * @return string 'text/html'
	 */
	public static function return_texthtml(){
		return "text/html";
	}
	
	/**
	 * WP_Mail doesn't accept attachment meta, only an array of paths, this function post-fixes attachments to the PHPMailer object.
	 * @param PHPMailer $phpmailer
	 * @param array $attachments
	 */
	public static function add_attachments_to_mailer( $phpmailer, $attachments = array() ){
		//add attachments
		$attachments = !empty($attachments) ? $attachments : self::$attachments;
		if( !empty($attachments) ){
			foreach($attachments as $attachment){
				$att = array('name'=> '', 'encoding' => 'base64', 'type' => 'application/octet-stream');
				if( is_array($attachment) ){
					$att = array_merge($att, $attachment);
				}else{
					$att['path'] = $attachment;
				}
				try{
					$phpmailer->addAttachment($att['path'], $att['name'], $att['encoding'], $att['type']);
				}catch( phpmailerException $ex ){
					//do nothing
				}
			}
		}
		self::$attachments = array();
	}
	
	public static function delete_email_attachments( $attachments ){
		foreach( $attachments as $attachment ){
			if( !empty($attachment['delete']) ){
				@unlink( $attachment['path']);
			}
		}
	}
	
	/**
	 * Returns the path of the attachments folder, creating it if non-existent. Returns false if folder could not be created.
	 * A .htaccess file is also attempted to be created, although this will still return as true even if it cannot be created.
	 * @return bool|string
	 */
	public static function get_attachments_dir(){
		//get and possibly create attachment directory path
		$upload_dir = wp_upload_dir();
		$attachments_dir = trailingslashit($upload_dir['basedir'])."em-email-attachments/";
		if( !is_dir($attachments_dir) ){
			//try to make a directory and create an .htaccess file
			if( @mkdir($attachments_dir, 0755) ){
				return $attachments_dir;
			}
			//could not create directory
			return false;
		}
		//add .htaccess file to prevent access to folder by guessing filenames
		if( !file_exists($attachments_dir.'.htaccess') ){
			$file = @fopen($attachments_dir.'.htaccess','w');
			if( $file ){
				fwrite($file, 'deny from all');
				fclose($file);
			}
		}
		return $attachments_dir;
	}
	
	/**
	 * Adds file to email attachments folder, which defaults to wp-content/uploads/em-email-attachments/ and returns the location of said file, false if file could not be created.
	 * @param $file_name
	 * @param $file_content
	 * @return bool|string
	 */
	public static function add_email_attachment( $file_name, $file_content ){
		$attachment_dir = self::get_attachments_dir();
		if( $attachment_dir ){
			$file = fopen($attachment_dir.$file_name,'w+');
			if( $file ){
				fwrite($file, $file_content);
				fclose($file);
				return $attachment_dir . $file_name;
			}
		}
		return false;
	}
}
?>