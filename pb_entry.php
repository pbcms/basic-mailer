<?php
    namespace Module;

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    use Library\ModuleConfig;
    use Library\Controller;
    use Library\Policy;
    use Library\Mailer;
    use Registry\Action;
    use Helper\ApiResponse as Respond;
    use Helper\Request;
    use Helper\Validate as Validator;

    require 'vendor/autoload.php';

    class BasicMailer {
        public function initialize() {
            $policy = new Policy();
            $config = new ModuleConfig('basic-mailer');
            $config->defaults(array(
                "enabled" => 0,
                "host" => "",
                "smtp_auth" => 0,
                "username" => "",
                "password" => "",
                "smtp_secure" => null,
                "port" => 25,
                "from" => $policy->get('site-email'),
                "from_name" => $policy->get('site-title'),
                "is_html" => 1
            ));

            if ($config->get('enabled') == 1) {
                Action::register('send-mail', function($options) { 
                    $config = new ModuleConfig('basic-mailer');
                    $mail = new PHPMailer(true);

                    if (!isset($options['isHTML'])) $options['isHTML'] = $config->get('is_html') == '1' ? true : false;

                    try {
                        $mail->isSMTP();
                        $mail->Host         = $config->get('host');
                        $mail->SMTPAuth     = ($config->get('smtp_auth') == '1' ? true : false);
                        $mail->Username     = $config->get('username');
                        $mail->Password     = $config->get('password');
                        switch($config->get('smtp_secure')) {
                            case 'tls':
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                break;
                            case 'starttls':
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                break;
                        }

                        $mail->Port         = $config->get('port'); 
                        $mail->setFrom($config->get('from'), $config->get('from_name'));
                        $mail->isHTML($options['isHTML']);
                        $mail->Subject = $options['subject'];
                        $mail->Body    = $options['message'];

                        if (is_array($options['recipient'])) {
                            foreach($options['recipient'] as $recipient) {
                                $mail->addAddress($recipient);
                            }
                        } else {
                            $mail->addAddress($options['recipient']);
                        }

                        $mail->send();

                        return (object) array(
                            "success" => true
                        );
                    } catch (Exception $e) {
                        return (object) array(
                            "success" => false,
                            "message" => $mail->ErrorInfo
                        );
                    }
                });
            }
        }

        public function requestHandler($params) {
            if (isset($params[0])) {
                switch($params[0]) {
                    case 'test-mail':
                        if (!Request::requireMethod('post')) die();
                        if (!Request::requireAuthentication()) die();

                        $controller = new Controller;
                        $userModel = $controller->__model('user');
                        if (!$userModel->check('module.basic-mailer.test-mail')) {
                            Respond::error("missing_privileges", "You are lacking the permission to send a test E-mail.");
                        } else {
                            $body = Request::parseBody();
                            if (isset($body->recipient)) {
                                $mailer = new Mailer;
                                $content = file_get_contents(DYNAMIC_DIR . '/modules/basic-mailer/test-mail.html');
                                $content = str_replace("{{SITE_LOCATION}}", SITE_LOCATION, $content);
        
                                $res = $mailer->send(array(
                                    "recipient" => $body->recipient, 
                                    "subject" => SITE_TITLE . ": Testing E-mail.", 
                                    "message" => $content, 
                                    "headers" => array(
                                        'Mime-Version' => '1.0',
                                        'Content-Type' => 'text/html;charset=UTF-8'
                                    ),
        
                                    //Optimal options for common mailer plugins.
                                    "isHTML" => true
                                ));
                                            
                                if ($res->success) {
                                    Respond::success();
                                } else {
                                    Respond::error($res->error, $res->message);
                                }
                            } else {
                                Respond::error('missing_recipient', "The recipient is missing from your request.");
                            }
                        }

                        break;
                    case 'retrieve-settings':
                        if (!Request::requireMethod('get')) die();
                        if (!Request::requireAuthentication()) die();

                        $controller = new Controller;
                        $userModel = $controller->__model('user');
                        if (!$userModel->check('module.basic-mailer.retrieve-settings')) {
                            Respond::error("missing_privileges", "You are lacking the permission to retrieve mailer settings.");
                        } else {
                            $config = new ModuleConfig('basic-mailer');
                            Respond::success(array(
                                "settings" => array(
                                    "enabled" => intval($config->get('enabled')) == 1,
                                    "is_html" => intval($config->get('is_html')) == 1,
                                    "host" => $config->get('host'),
                                    "port" => intval($config->get('port')),
                                    "smtp_secure" => $config->get('smtp_secure'),
                                    "from" => $config->get('from'),
                                    "from_name" => $config->get('from_name'),
                                    "smtp_auth" => intval($config->get('smtp_auth')) == 1,
                                    "username" => $config->get('username'),
                                    "password" => $config->get('password')
                                )
                            ));
                        }   

                        break;
                    case 'save-settings':
                        if (!Request::requireMethod('post')) die();
                        if (!Request::requireAuthentication()) die();

                        $controller = new Controller;
                        $userModel = $controller->__model('user');
                        if (!$userModel->check('module.basic-mailer.save-settings')) {
                            Respond::error("missing_privileges", "You are lacking the permission to retrieve mailer settings.");
                        } else {
                            $body = Request::parseBody();
                            $config = new ModuleConfig('basic-mailer');
                            $allowed = ["enabled", "is_html", "host", "port", "smtp_secure", "from", "from_name", "smtp_auth", "username", "password"];

                            $body = Validator::removeUnlisted($allowed, $body);
                            foreach($body as $setting => $value) {
                                if (in_array($setting, ["enabled", "is_html", "smtp_auth"])) $value = $value ? 1 : 0;
                                $config->set($setting, $value);
                            }

                            Respond::success();
                        }   

                        break;
                    default:
                        Respond::error('unknown_action', "The requested action does not exist.");
                }
            } else {
                Respond::error('missing_action', "The action was missing from the url.");
            }
        }

        public function configurator($params) {
            require_once DYNAMIC_DIR . '/modules/basic-mailer/configurator.php';
        }
    }