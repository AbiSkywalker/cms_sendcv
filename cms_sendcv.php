<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class cms_sendcv extends Module
{
    public function __construct()
    {
        $this->name = 'cms_sendcv';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Abi Ramírez';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('CMS Send CV');
        $this->description = $this->l('Displays a form for your clients to send you their CV');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayCMSSendCV');

        
        $this->emptyTemplatesCache();

        return (bool) $return;
    }


    public function getWidgetVariables()
    {
        $notifications = false;
        $job_positions = Configuration::get('CMS_JOB_POSITIONS');

        if (Tools::isSubmit('submitMessage')) {
            $this->sendMessage();

            if (!empty($this->context->controller->errors)) {
                $notifications['messages'] = $this->context->controller->errors;
                $notifications['nw_error'] = true;
            } elseif (!empty($this->context->controller->success)) {
                $notifications['messages'] = $this->context->controller->success;
                $notifications['nw_error'] = false;

                $thankyou_cvRedirectUrl = $this->context->link->getCMSLink('15', null);
                Tools::redirect($thankyou_cvRedirectUrl);
            }
        } 
        
        $this->contact['allow_file_upload'] = 1; //(bool) Configuration::get('PS_CUSTOMER_SERVICE_FILE_UPLOAD');

        return [
            'contact' => $this->contact,
            'notifications' => $notifications,
            'job_positions' => $job_positions
        ];
    }
    
    public function hookDisplayCMSSendCV(array $params)
    {

        if (!$this->active) {
            return;
        }
        
        $this->smarty->assign($this->getWidgetVariables());

        return $this->display(__FILE__, 'views/templates/hook/cms_sendcv.tpl');
    }


    public function displayForm()
    {
        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Send CV to'),
                        'name' => 'CMS_SEND_CV_TO',
                        'required' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Job positions ( value | key; Separate options using ";" ).'),
                        'name' => 'CMS_JOB_POSITIONS',
                        'required' => false,
                        'autoload_rte' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value['CMS_SEND_CV_TO'] = Tools::getValue('CMS_SEND_CV_TO', Configuration::get('CMS_SEND_CV_TO'));
        $helper->fields_value['CMS_JOB_POSITIONS'] = Tools::getValue('CMS_JOB_POSITIONS', Configuration::get('CMS_JOB_POSITIONS'));

        return $helper->generateForm([$form]);
    }

    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content 
     */
    public function getContent()
    {
        $output = '';

        // this part is executed only when the form is submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            // retrieve the values set by the user
            $send_cv_to = (string) Tools::getValue('CMS_SEND_CV_TO');
            $job_positions = (string) Tools::getValue('CMS_JOB_POSITIONS');

            $send_cv_to_value = str_replace(array("\r\n", "\n\r", "\r", "\n"), "<br />", $send_cv_to);
           
            // update it and display a confirmation message
            Configuration::updateValue('CMS_SEND_CV_TO', $send_cv_to_value);
            Configuration::updateValue('CMS_JOB_POSITIONS', $job_positions);
            $output = $this->displayConfirmation($this->l('Settings updated'));
        }

        // display any message, then the form
        return $output . $this->displayForm();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function sendMessage()
    {
        $fullname = trim(Tools::getValue('fullname'));
        $phone = trim(Tools::getValue('phone'));
        $email = trim(Tools::getValue('email'));
        $location = trim(Tools::getValue('location'));
        $job_position = trim(Tools::getValue('position[]'));
        
        $extension = ['.txt', '.rtf', '.doc', '.docx', '.pdf', '.zip', '.png', '.jpeg', '.gif', '.jpg'];
        $file_attachment = Tools::fileAttachment('cvFileUpload');
                

        if (!($email = trim(Tools::getValue('email'))) || !Validate::isEmail($email)) {
            $this->context->controller->errors[] = $this->trans(
                    'Invalid email address.', [], 'Modules.CmsSendCV.Shop'
            );
        } elseif (empty($fullname)) {
            $this->context->controller->errors[] = $this->trans(
                    'The name cannot be blank.', [], 'Modules.CmsSendCV.Shop'
            );
        }elseif (!empty($file_attachment['name']) && $file_attachment['error'] != 0) {
            $this->context->controller->errors[] = $this->trans(
                'An error occurred during the file-upload process.',
                [],
                'Modules.CmsSendCV.Shop'
            );
        } elseif (!empty($file_attachment['name']) &&
                  !in_array(Tools::strtolower(Tools::substr($file_attachment['name'], -4)), $extension) &&
                  !in_array(Tools::strtolower(Tools::substr($file_attachment['name'], -5)), $extension)
        ) {
            $this->context->controller->errors[] = $this->trans(
                'Bad file extension',
                [],
                'Modules.CmsSendCV.Shop'
            );
        } else {
            $sendNotificationEmail = 1;
            if (!count($this->context->controller->errors) && empty($mailAlreadySend) && ($sendNotificationEmail)
            ) {

                $temp = explode(".", $file_attachment['rename']);
                //año-mes-día-curriculum-nombre
                $cv_file_name = date('Y-m-d').'-curriculum-'.trim(strip_tags(str_replace(' ','-',$fullname)));
                $new_filename = $cv_file_name.'.'.end($temp);

                $file_attachment['rename'] = $new_filename;

                $testFileUpload = (isset($file_attachment['rename']) && !empty($file_attachment['rename']));


                if ($testFileUpload && rename($file_attachment['tmp_name'], _PS_UPLOAD_DIR_ . 'curriculums/' . basename($file_attachment['rename']))) {
                    @chmod(_PS_UPLOAD_DIR_ . 'curriculums/' . basename($file_attachment['rename']), 0664);
                }
                

                $var_list = [
                    '{fullname}' => Tools::nl2br(Tools::htmlentitiesUTF8(Tools::stripslashes($fullname))),
                    '{phone}' => Tools::nl2br(Tools::htmlentitiesUTF8(Tools::stripslashes($phone))),
                    '{email}' => $email,
                    '{location}' => Tools::nl2br(Tools::htmlentitiesUTF8(Tools::stripslashes($location))),
                    '{attached_file}' => '-',
                    '{job_position}' => $job_position,
                ];
                
                if (isset($file_attachment['name'])) {
                    $var_list['{attached_file}'] = $file_attachment['name'];
                }

                if ($sendNotificationEmail) {
                    $res_mail = Mail::Send(
                        $this->context->language->id,  // defaut language id
                        'cms_sendcv',  // email template file to be use
                        'Nuevo cv - ' . $fullname, // email subject
                        array(
                            '{email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                            '{message}' => $var_list // email content
                        ),
                        Configuration::get('CMS_SEND_CV_TO'), // receiver email address
                        NULL, //receiver name
                        $email, //from email address
                        'Salchicheros CV', //from name
                        $file_attachment, //file attachment
                        NULL, //mode smtp
                        _PS_MODULE_DIR_ . 'cms_sendcv/mails' //custom template path
            
                    );

                    if (!$res_mail) {
                        $this->context->controller->errors[] = $this->trans(
                                'An error occurred while sending the message.', [], 'Modules.CmsSendCV.Shop'
                        );
                    }
                }
            }

            if (!count($this->context->controller->errors)) {
                $this->context->controller->success[] = $this->trans(
                        'Your message has been successfully sent to our team. Thank you for helping us improve', [], 'Modules.CmsSendCV.Shop'
                );
            }
        }
    }

}
