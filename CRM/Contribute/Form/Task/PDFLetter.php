<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to create PDF letter for a group of contacts or a single contact.
 */
class CRM_Contribute_Form_Task_PDFLetter extends CRM_Contribute_Form_Task {

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'CommaSeparatedIntegers', $this, FALSE);
    if (!empty($this->_caseId) && strpos($this->_caseId, ',')) {
      $this->_caseIds = explode(',', $this->_caseId);
      unset($this->_caseId);
    }

    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'CommaSeparatedIntegers', $this, FALSE);

    $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($cid) {
      CRM_Contact_Form_Task_PDFLetterCommon::preProcessSingle($this, $cid);
      $this->_single = TRUE;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_activityId)) {
      $params = ['id' => $this->_activityId];
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('details', $defaults);
    }
    else {
      $defaults['thankyou_update'] = 1;
    }
    $defaults = $defaults + CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);

    // Build common form elements
    CRM_Contribute_Form_Task_PDFLetterCommon::buildQuickForm($this);

    // specific need for contributions
    $this->add('static', 'more_options_header', NULL, ts('Thank-you Letter Options'));
    $this->add('checkbox', 'receipt_update', ts('Update receipt dates for these contributions'), FALSE);
    $this->add('checkbox', 'thankyou_update', ts('Update thank-you dates for these contributions'), FALSE);

    // Group options for tokens are not yet implemented. dgg
    $options = [
      '' => ts('- no grouping -'),
      'contact_id' => ts('Contact'),
      'contribution_recur_id' => ts('Contact and Recurring'),
      'financial_type_id' => ts('Contact and Financial Type'),
      'campaign_id' => ts('Contact and Campaign'),
      'payment_instrument_id' => ts('Contact and Payment Method'),
    ];
    $this->addElement('select', 'group_by', ts('Group contributions by'), $options, [], "<br/>", FALSE);
    // this was going to be free-text but I opted for radio options in case there was a script injection risk
    $separatorOptions = ['comma' => 'Comma', 'td' => 'Horizontal Table Cell', 'tr' => 'Vertical Table Cell', 'br' => 'Line Break'];

    $this->addElement('select', 'group_by_separator', ts('Separator (grouped contributions)'), $separatorOptions);
    $emailOptions = [
      '' => ts('Generate PDFs for printing (only)'),
      'email' => ts('Send emails where possible. Generate printable PDFs for contacts who cannot receive email.'),
      'both' => ts('Send emails where possible. Generate printable PDFs for all contacts.'),
    ];
    if (CRM_Core_Config::singleton()->doNotAttachPDFReceipt) {
      $emailOptions['pdfemail'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for contacts who cannot receive email.');
      $emailOptions['pdfemail_both'] = ts('Send emails with an attached PDF where possible. Generate printable PDFs for all contacts.');
    }
    $this->addElement('select', 'email_options', ts('Print and email options'), $emailOptions, [], "<br/>", FALSE);

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Make Thank-you Letters'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Done'),
      ],
    ]);

  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contribute_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    return $tokens;
  }

}
