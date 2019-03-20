<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$email_detail = EXT_NAME . '\'s '; 
$email_detail .=<<<HERE
Composer Is just like the built in "Communicate" utility with one enhancement. It provides options for: <blockquote>
	<ol>
		<li>Pasting the contents of a csv file</li>
		<li>Upload a csv file to be scanned</li>
	</ol>
</blockquote>
<h3>Required Columns: </h3>
<dl>
	<dt>An Email Column:</dt>
	<dd>column title is some form of the following string(email, mail, e-mail, address)</dd>
	<dt>An First Name Column:</dt><dd>column title is some form of the following string(first, given, forename)</dd>
	<dt>An Last Name Column:</dt><dd>column title is some form of the following string(last, surname)</dd>
</dl>
<p>The rest of the column headings will be parsed and provided as "tokenized" placeholders after: pasting/uploading file</p>', 


HERE;
$lang = array(
	'intro_title' => 'Overview',
	'intro_heading' => EXT_DISPLAY_NAME.' Overview',
	'intro_text' => ($test = '<p>'.EXT_NAME.' will route all emails generated by ExpressionEngine&reg; through a supported third-party transactional email service.</p>'),
	'compose_name' => 'Compose New Email',
	'config_warning_heading' => EXT_SHORT_NAME.' is configured elsewhere',
	'config_warning_text' => 'You appear to have '.EXT_NAME.' configured via config.php, so changes you make here may be overridden.',
	'optional' => '(Optional)',
	'services_title' => 'Services',
	'services' => 'Services',
	'services_heading' => 'Services Overview',
	'services_text' => '<p>Activate and configure as many services as you like via the links in the sidebar, and change the service order by dragging each service name into a new position within the sidebar.</p>
	<p>If the topmost activated service fails to send a particular email, the next active service will be used. If all active services fail, your email will be sent via ExpressionEngine&reg;&rsquo;s default email method.</p>',
	'status' => 'Service Status',
	'enabled' => 'Enabled',
	'disabled' => 'Disabled',
	'description' => 'Description',
	'save_settings' => 'Save Settings',
	'mandrill_name' => 'Mandrill',
	'fakeservice_name' => 'FakeService',
	'fakeservice_description' => 'FakeService is a service offered by MailChimp as an add-on to their paid monthly accounts. Sign-up at <a href="%s">http://mandrill.com</a>.',
	'mandrill_link' => 'http://mandrill.com',
	'mandrill_description' => 'Mandrill is a service offered by MailChimp as an add-on to their paid monthly accounts. Sign-up at <a href="%s">http://mandrill.com</a>.',
	'mandrill_api_key' => 'API Key',
	'mandrill_subaccount' => 'Subaccount <i>(optional)</i>',
	'postmark_name' => 'Postmark',
	'postmark_description' => 'Postmark offers your first 25,000 email sends free, followed by pay-as-you-go pricing. Sign-up at <a href="%s">http://postmarkapp.com</a>. <i>Note: the From address of each email must be approved in your Postmark dashboard in order to send successfully.</i>',
	'postmark_link' => 'http://postmarkapp.com',
	'postmark_api_key' => 'API Key',	
	'postageapp_name' => 'PostageApp',
	'postageapp_description' => 'PostageApp offers 100 email sends per day on their free plan. Sign-up at <a href="%s">http://postageapp.com</a>.',
	'postageapp_link' => 'http://postageapp.com',
	'postageapp_api_key' => 'API Key',
	'sendgrid_name' => 'SendGrid',
	'sendgrid_description' => 'SendGrid offers 12,000 email sends per month on their free plan. Sign-up at <a href="%s">http://sendgrid.com</a>.',
	'sendgrid_link' => 'http://sendgrid.com',
	'sendgrid_api_key' => 'API Key',	
	'sparkpost_name' => 'SparkPost',
	'sparkpost_description' => 'SparkPost offers 100,000 email sends per month on their free plan. Sign-up at <a href="%s">http://sparkpost.com</a>.',
	'sparkpost_link' => 'http://sparkpost.com',
	'sparkpost_api_key' => 'API Key',
	'mailgun_name' => 'MailGun',
	'mailgun_description' => 'MailGun is run by RackSpace, and offers 10,000 email sends per month on their free plan. Sign-up at <a href="%s">http://mailgun.com</a>.',
	'mailgun_link' => 'http://mailgun.com',
	'mailgun_api_key' => 'API Key',	
	'mailgun_domain' => 'Domain Name',
	'missing_service_credentials' => 'You have %s activated as a service in '.EXT_NAME.', but you are missing some required credentials to send email with this service. Please visit your '.EXT_NAME.' settings screen to fix this.',
	'could_not_deliver' => EXT_SHORT_NAME.' tried to deliver your email with %s but the service failed.',
	'email_heading' => 'Email Functions',
	'email_title' => 'Email Functions',
	'email_text' => $email_detail,
	'email_title' => 'Email Functions',
	'compose_title' => EXT_NAME.'\'s Composer',
	'compose_detail' => EXT_NAME . ' provides a few methods for adding recipents: default, csv pasting, csv import',
	'compose_heading' => 'Compose Email ',
	'compose_desc' => EXT_NAME.' should be familiar. It is the same as the default ExpressionEngine "Communicate"',
	'compose_send_email' => 'Send Email',
	'compose_sending_email' => 'Sending Email',
	'sent_name' => 'View Sent Mail',
	'your_email' => 'Your Email',
	'email_subject' => 'Email Subject',
	'email_body' => 'Email Body',
	'attachment' => 'Attachment',
	'attachment_desc' => 'Attachments are not saved, after sending.',
	'recipient_options' => 'Recipient Options',
	'primary_recipient_type' => 'Recipent Entry Type',
	'primary_recipient_type_desc' => 'Enable CSV recipient option?',
	'primary_recipients' => 'Primary recipient(s)',
	'primary_recipients_desc' => 'To Email(s). Separate multiple recipients with a comma.',
	'compose_default_recipient_type' => 'Default',
	'csv_recipient' => 'CSV data',
	'csv_recipient_desc' => 'Raw CSV data',
	'file_recipient' => 'CSV file upload',
	'file_recipient_desc' => 'Upload csv file',
	'compose_file_recipient_type' => ($recip_file_type = 'Upload CSV'),
	'compose_csv_recipient_type' => ($recip_csv_type = 'CSV'),
	'compose_error' => 'Attention: Email not sent',
	'compose_error_desc' => 'We were unable to send this Email, please review and fix errors below.',
	'create_new_email' => 'Create New Email?',
	'send_as' => 'Send As: ',
	'word_wrap' => 'Word Wrap',
	'plaintext_alt' => 'Alternate content for your HTML Email, will be delivered in Plain Text, when an Email application cannot render HTML.',
	'plaintext_body' => 'Plain Text Body',
	'emails_removed' => 'Emails Removed',
	'recipient_type' => 'Recipient Entry Method',
	'recipient_type_desc' => "Default: Type email <br/>{$recip_csv_type}: Paste contents of CSV File <br/> {$recip_file_type}: Upload local CSV File", 
	'recipients' => 'Recipient(s)',
	'cc_recipients' => 'CC recipient(s)',
	'cc_recipients_desc' => 'CC Email(s). Separate multiple recipients with a comma.',
	'bcc_recipients' => 'BCC recipient(s)',
	'bcc_recipients_desc' => 'BCC Email(s). Separate multiple recipients with a comma.',
	'add_member_groups' => 'Add member group(s)',
	'add_member_groups_desc' => 'Send Email to all members in chosen group(s).',
	'view_email_cache' => 'Sent Emails',
	'no_cached_emails' => 'No Sent Email',
	'search_emails_button' => 'Search Emails',
);