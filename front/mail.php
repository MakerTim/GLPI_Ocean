<?php

use Ocean\FastTicket;
use Ocean\I18N;

define('GLPI_ROOT', '../../../');
include '../api/include.php';

header('Content-Type: image/png');

$lang = key_exists('LANG', $_GET) ? $_GET['LANG'] : 'default';
if (key_exists('ticketid', $_GET)) {
	$ticket = FastTicket::getTicketWithLeftJoins($_GET['ticketid']);
	if ($ticket) {
		FastTicket::bindTicketDetails($ticket);
	} else {
		die(file_get_contents('404.png'));
	}
}

drawTicketDialog($ticket);

/**
 * @param $ticket FastTicket
 */
function drawTicketDialog($ticket) {
	global $img, $fontMain, $fontFA, $color_text, $color_text2, $color_text3;

	$width = 1080;
	$height = 720;
	$fontMain = __DIR__ . '\\Ubuntu-Regular.ttf';
	$fontFA = __DIR__ . '\\FA5.ttf';
	$img = @imagecreate($width, $height) or die(file_get_contents('500.png'));

	$color_alpha = imagecolorallocatealpha($img, 255, 255, 255, 127);
	$color_background = imagecolorallocatealpha($img, 255, 255, 255, 12);
	$color_outline = imagecolorallocate($img, 55, 101, 148);
	$color_outlines = [ //
		imagecolorallocate($img, 100, 100, 100), //
		imagecolorallocate($img, 0, 165, 120), //
		imagecolorallocate($img, 0, 125, 80), //
		imagecolorallocate($img, 10, 10, 10), //
	];
	$color_text = imagecolorallocate($img, 0, 0, 0);
	$color_text2 = imagecolorallocate($img, 50, 50, 50);
	$color_text3 = imagecolorallocate($img, 100, 100, 100);

	// background
	imagefilledrectangle($img, 0, 0, $width, $height, $color_alpha);
	imagefilledrectangle($img, 11, 11, $width - 11, $height - 11, $color_background);

	// texts
	drawText18n(35, 70, 50, 'mail.title', $color_outline);
	drawText18n(35, 120, 20, 'mail.type.ticket');
	drawText18n(50, 150, 20, $ticket['name']);
	$content = preg_replace('/<[^>]*>/m', '', $ticket['content']);
//	$content = preg_replace('/./', '-', $content);
	$content = htmlentities($content);
	$content = preg_replace('/&[^;]+;/m', ' ', $content);
//	$content = $ticket['content'];
	$charLen = 70;
	for ($i = 0; $i < strlen($content) / $charLen; $i++) {
		$lastChars = '';
		if ($i === 3) {
			break;
		}
		if ($i === 2) {
			$lastChars = '...';
		}
		drawText18n(60, 200 + ($i * 30), 20, substr($content, $i * $charLen, $charLen) . $lastChars, $color_text2);
	}

	// outlines
	for ($i = 0; $i < 5; $i++) {
		// top
		imageline($img, 10, 10 + $i, $width - 10, 10 + $i, $color_outline);
		// left
		imageline($img, 10 + $i, 10, 10 + $i, $height - 10, $color_outline);
		// right
		imageline($img, $width - 10 - $i, 10, $width - 10 - $i, $height - 10, $color_outline);
		// bottom
		imageline($img, 10, $height - 10 - $i, $width - 10, $height - 10 - $i, $color_outline);
	}

	// stages
	$staged = [1, 2, 4, 5, 6];
	$stages = count($staged);
	for ($stage = 0; $stage < $stages; $stage++) {
		$status = valueOfType(FastTicket::STATUS, $ticket['status']);
		$i = $status === $staged[$stage] ? 2 : ($status > $staged[$stage] ? 1 : 0);
		$color = $color_outlines[$i];


		if ($i === 0) { // to be worked on
			$FA = '';
		} else if ($i === 1) { // finished
			$FA = '';
		} else { // 2 working on
			$FA = '';
		}

		drawText18n($width / $stages * $stage + 40, 387, 20, $FA, $color, $fontFA);
		drawText18n($width / $stages * $stage + 70, 385, 20, nameOfType(FastTicket::STATUS, $staged[$stage]), $color);
		for ($i = 0; $i < 5; $i++) {
			// top
			imageline($img, $width / $stages * $stage + 30, 300 + $i, $width / $stages * ($stage + 1) - 30, 300 + $i, $color);
			// left
			imageline($img, $width / $stages * $stage + 30 + $i, 300, $width / $stages * $stage + 30 + $i, 450, $color);
			// right
			imageline($img, $width / $stages * ($stage + 1) - 30 - $i, 300, $width / $stages * ($stage + 1) - 30 - $i, 450, $color);
			// bottom
			imageline($img, $width / $stages * $stage + 30, 450 + $i, $width / $stages * ($stage + 1) - 30, 450 + $i, $color);

			// arrow
			if ($stage !== $stages - 1) {
				$I = $status === $staged[$stage + 1] ? 2 : ($status > $staged[$stage] ? 1 : 0);
				$Color = $color_outlines[$I];
				//horizontal
				imageline($img, $width / $stages * ($stage + 1) - 30, 375 + $i, $width / $stages * ($stage + 1) + 30, 375 + $i, $Color);
				//top
				imageline($img, $width / $stages * ($stage + 1) - $i, 345, $width / $stages * ($stage + 1) + 30, 375 + $i, $Color);
				//bottom
				imageline($img, $width / $stages * ($stage + 1) + $i, 405, $width / $stages * ($stage + 1) + 30, 375 + $i, $Color);
			}
		}
	}

	// assignedto txt
	drawText18n(35, 530, 20, 'mail.assignedto');
	$assignees = '';
	foreach ($ticket['assigned_users'] as $assignedUser) {
		$assignees .= $assignedUser['assigned_users'] . ', ';
	}
	foreach ($ticket['assigned_groups'] as $assignedGroup) {
		$assignees .= $assignedGroup['assigned_groups'] . ', ';
	}
	if (strlen($assignees) > 0) {
		$assignees = str_replace(', )', '', $assignees . ')');
	} else {
		$assignees = 'mail.none';
	}
	drawText18n(70, 560, 20, $assignees);
	drawText18n(35, 590, 20, 'mail.followedby');
	$followers = '';
	if (count($ticket['followed_groups']) > 0) {
		foreach ($ticket['followed_groups'] as $assignedGroup) {
			$followers .= $assignedGroup['followed_groups'] . ', ';
		}
		$followers = str_replace(', )', '', $followers . ')');
	} else {
		$followers = 'mail.none';
	}
	drawText18n(70, 620, 20, $followers);
	drawText18n(35, 650, 20, 'mail.madeby');
	drawText18n(70, 680, 20, $ticket['users_id_recipient'] . ' ' . $ticket['users_id_recipient2']);

	imagepng($img);
	imagedestroy($img);
}

function drawText18n($x, $y, $size, $text, $color = null, $font = null) {
	global $img, $fontMain, $lang;
	if (!$font) {
		$font = $fontMain;
	}
	if ($color == null) {
		global $color_text;
		$color = $color_text;
	}

	$text = I18N::translate($text, $lang);

	imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}
