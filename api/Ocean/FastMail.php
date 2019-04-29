<?php

namespace Ocean;

include GLPI_ROOT . 'inc/toolbox.class.php';
foreach (glob(GLPI_ROOT . 'vendor/phpmailer/phpmailer/src/*.php') as $filename) {
	include $filename;
}

use PHPMailer\PHPMailer\PHPMailer;
use Toolbox;

class FastMail extends PHPMailer {

	public static function mailTicket(FastUser $user, $ticketId) {
		$baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
		$imgUrl = $baseUrl . str_replace('api/fastAPI.php', 'front/mail.php?ticketid=' . $ticketId, $_SERVER['SCRIPT_NAME']);
		$imgUrl .= '&LANG=' . I18N::parseLang($user->language);
		$ticketUrl = getBaseGLPIOceanURL() . 'open/Ticket/' . $ticketId;

		return FastMail::mail($user, 'mail.title', //
			[ //
				"<a href='$ticketUrl'><img src='$imgUrl' alt='GLPI ticket status'></a>", //
				"mail.followticketalt <a	href='$ticketUrl'>" . I18N::translate('mail.here', $user->language) . "</a>",//
			], "mail.followticketalt $ticketUrl");
	}

	public static function mail(FastUser $userTo, $i18nSubject, $i18nContent, $i18nAltContent = null) {
		$GLPI_CONFIG = FastConfig::getConfig();
		$mail = new FastMail();
		$mail->addAddress($userTo->getMail(), $userTo->firstname);
		$mail->setFrom($GLPI_CONFIG['smtp_sender'] ? $GLPI_CONFIG['smtp_sender'] : $GLPI_CONFIG['admin_email']);
		$mail->Subject = I18N::translate($i18nSubject, $userTo->language);
		$mail->isHTML(true);

		if (is_array($i18nContent)) {
			$i18nContents = $i18nContent;
			$i18nContent = '';
			foreach ($i18nContents as $i18n) {
				$i18nContent .= I18N::translate($i18n, $userTo->language);
				$i18nContent .= PHP_EOL;
			}
		}

		$mail->Body = '
		<html lang="' . I18N::parseLang($userTo->language) . '">
			<body>
			' . preg_replace('/[\n\r]/m', '<br>', $i18nContent) . '
			' . preg_replace('/[\n\r]/m', '<br>', $GLPI_CONFIG['mailing_signature']) . '
			</body>
		</html>';

		if ($i18nAltContent === null) {
			$mail->AltBody = preg_replace('/<(?:.|\n)*?>/m', '', $i18nContent);
		} else {
			$mail->AltBody = I18N::translate($i18nAltContent, $userTo->language);
		}

		$mail->log('FROM: ' . $mail->From);
		$mail->log('TO: ' . $mail->getTo());
		$mail->log('BODY: ' . $mail->Body);
		$sended = $mail->send();

		$mail->log($sended ? 'Successfully send mail!' : 'Failed sending mail:' . PHP_EOL . $mail->ErrorInfo);

		return $mail->log;
	}

	public $log = [];

	public function __construct() {
		parent::__construct(null);

		$this->WordWrap = 80;
		$this->CharSet = "utf-8";

		$GLPI_CONFIG = FastConfig::getConfig();
		$this->log('Mailing in mode ' . $GLPI_CONFIG['smtp_mode']);
		$this->Debugoutput = function ($message) {
			$this->log('debugging: ' . $message);
		};
		if ($GLPI_CONFIG['smtp_mode'] != 0) {
			// SMTP
			$this->Mailer = "smtp";
			$this->Host = $GLPI_CONFIG['smtp_host'] . ':' . $GLPI_CONFIG['smtp_port'];
			if ($GLPI_CONFIG['smtp_username'] != '') {
				$this->SMTPAuth = true;
				$this->Username = $GLPI_CONFIG['smtp_username'];
				$this->Password = Toolbox::decrypt($GLPI_CONFIG['smtp_passwd'], "GLPI£i'snarss'ç");
				$this->log('Using smtp auth {' . //
					$this->Username . ' : ' . preg_replace('/./', '●', $this->Password) . //
					'}');
			}
			if ($GLPI_CONFIG['smtp_mode'] == 2) {
				$this->SMTPSecure = 'ssl';
				$this->log('using SSL');
			}
			if ($GLPI_CONFIG['smtp_mode'] == 3) {
				$this->SMTPSecure = 'tls';
				$this->log('using TLS');
			}
			if (!$GLPI_CONFIG['smtp_check_certificate']) {
				$this->log('disable certificate check');
				$this->SMTPOptions = //
					[ //
						'ssl' => [ //
							'verify_peer' => false, //
							'verify_peer_name' => false, //
							'allow_self_signed' => true //
						] //
					];
			}
			if ($GLPI_CONFIG['smtp_sender'] != '') {
				$this->log('Sending from: ' . $GLPI_CONFIG['smtp_sender']);
				$this->Sender = $GLPI_CONFIG['smtp_sender'];
			}
		}
	}

	public function getTo() {
		$to = '';
		foreach (parent::getToAddresses() as $toAddress) {
			$to .= $toAddress[0] . ' ';
		}
		return \trim($to);
	}

	/**
	 * @param $logging string
	 */
	private function log($logging) {
		$this->log[] = $logging;
	}
}
