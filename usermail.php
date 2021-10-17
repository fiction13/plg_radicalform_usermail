<?php
/*
 * @package   plg_radicalform_usermail
 * @version   1.0.0
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2021 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Usermail radicalform plugin.
 *
 * @package   plg_radicalform_usermail
 * @since     1.0.0
 */
class plgRadicalformUserMail extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	public function onBeforeSendRadicalForm($clear, &$input, $params)
	{
	    $config = Factory::getConfig();
	    $mailer = Factory::getMailer();

	    // Params
	    $emailReply     = $this->params->get('email_reply') ? $this->params->get('email_reply') : $config->get('mailfrom');
	    $email          = $input[$params->get('replyto')];
	    $targetId       = isset($input['rfTarget']) ? $input['rfTarget'] : '';
	    $targetIdParam  = $this->params->get('target_id') ? array_map('trim', explode(',', $this->params->get('target_id'))) : array();

	    // Check target
	    if (!empty($targetId) && !empty($targetIdParam) && !in_array($targetId, $targetIdParam)) {
	        return;
        }

        // Get subject
	    if (isset($input["rfSubjectUserMail"]) && (!empty($input["rfSubjectUserMail"]))) {
			$subject = trim(strip_tags($input["rfSubjectUserMail"]));
			unset($input["rfSubjectUserMail"]);
		} else {
			$subject = $this->params->get('email_subject');
		}

	    // Get text
	    if (isset($input["rfUserMailText"]) && (!empty($input["rfUserMailText"]))) {
			$emailText = trim(strip_tags($input["rfUserMailText"]));
			unset($input["rfUserMailText"]);
		} else {
			$emailText = $this->params->get('email_text');
		}

	    // Check empty
	    if (empty($email) || empty($emailText)) {
	        return;
        }

	    // Get sender
        $sender = array(
			$config->get('mailfrom'),
			$config->get('fromname')
		);

	    // Get body
        $path = PluginHelper::getLayoutPath('radicalform', 'usermail');

		ob_start();
		include $path;
		$body = ob_get_clean();

	    $mailer->setSender($sender);
	    $mailer->setSubject($subject);
	    $mailer->isHtml(true);
	    $mailer->Encoding = 'base64';
	    $mailer->addReplyTo($emailReply);
	    $mailer->addRecipient($email);
	    $mailer->setBody($body);

	    $send = $mailer->Send();

	    if ($send !== true) {
            $message = $send->getMessage();
            $this->log('plg_system_radicalform_usermail', 'Mail error', $message);
        }

        return;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $message
	 * @return void|null
	 * @since 1.0.0
	 */
	protected function log($name, $value, $message) {
		if (empty($message)) {
			return;
		}

		if (is_array($message)) {
			$message = print_r($message, true);
		}

		// Add logger
        Log::addLogger(
	        array(
		        'text_file' => $name.'.log.php'
	        ),
	        JLog::ALL,
	        array($name)
        );

        // Add message to log
        Log::add($value.': '.$message, JLog::INFO, $name);

        return null;
	}
}
