<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2014 Alexander Bigga <linux@bigga.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * ab_booking form functions
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
class tx_abbooking_form {

	/**
	 * Request Formular
	 * The customer enters his personal data and submits the form.
	 *
	 * @param	[type]		$conf: ...
	 * @param	integer		$stage of booking process
	 * @param	[type]		$stage: ...
	 * @return	HTML		form with booking details
	 */
	public function printUserFormElements($showErrors = 0, $showHidden = 0) {

		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
		$customer = $this->lConf['customerData'];

		foreach ($this->lConf['form'] as $formname => $form) {
			// skip settings which are no form fields
			if (!is_array($form))
				continue;

			$formname = str_replace('.', '', $formname);
			if ($form['required'] == 1) {
				$cssClass = 'item ' . $formname . ' required';
				$required = 'required="required"';
			}
			else {
				$cssClass = 'item ' . $formname;
				$required = '';
			}

			// the parameter name
			$formnameGET = $this->prefixId.'['.$formname.']';

			// the parameter ID
			$formnameID = $this->prefixId.'-'.$formname;

			unset($cssError);
			switch ($form['type']) {
				case 'input':

					if (!empty($form['error']))
						$cssClass .= ' errorField';
					$out .= '<div class="'.$cssClass.'">';
					if (count($form['info.']) > 0 && $form['info.']['useTooltip'] == '1')
						$out .= '<label for="'.$formnameID.'" class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</label>';
					else {
						$out .= '<label for="'.$formnameID.'" class="title">'.$this->getTSTitle($form['title.']).'</label>';
						if (count($form['info.'])>0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}
					if (!empty($form['error'])) {
						$cssError = 'class="error"';
						$out .= '<p class="errorText">'.$form['error'].'</p>';
					}
					if ($showHidden == 1) {
						$type = 'hidden';
						$out .= '<p class="yourSettings">'.$customer[$formname].'</p>';
					} else {
						switch ($form['validation']) {
							case 'email': 	$type = 'email';
											break;
							case 'number': 	$type = 'number';
											break;
							case 'tel': 	$type = 'tel';
											break;
							default:	$type = 'text';
						}
					}

					if ($formname == 'checkinDate' && $showHidden == 0) {
						$out .= '<input class="'.$cssError.' datepicker" id="'.$formnameID.'" name="'.$formnameGET.'" type="'.$type.'" size="'.$form['size'].'" maxlength="'.(empty($form['maxsize']) ? $form['size'] : $form['maxsize'] ).'" value="'.date($this->lConf['dateFormat'], $this->lConf['startDateStamp']).'" '.$required.' />';
						$cssDatepicker = 'datepicker';
					}
					else
						$out .= '<input class="'.$cssError.'" id="'.$formnameID.'" name="'.$formnameGET.'" type="'.$type.'" size="'.$form['size'].'" maxlength="'.(empty($form['maxsize']) ? $form['size'] : $form['maxsize'] ).'" value="'.$customer[$formname].'" '.$required.' />';

					$out .= '</div>';

					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;

				case 'radio':
					$out .= '<fieldset class="'.$cssClass.'">';

					if (count($form['info.'])>0 && $form['info.']['useTooltip'] == '1')
						$out .= '<legend class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</legend>';
					else {
						$out .= '<legend class="title">'.$this->getTSTitle($form['title.']).'</legend>';
						if (count($form['info.']) > 0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}

					if ($showHidden == 1) {
						foreach ($form['radio.'] as $radioname => $radio) {
								if ($radioname == $customer[$formname])
									break;
						}
						$out .= '<p class="yourSettings">'.$this->getTSTitle($radio['title.']).'</p>';
						$out .= '<input type="hidden" name="'.$formnameGET.'" value="'.$customer[$formname].'">';
					}
					else {
						foreach ($form['radio.'] as $radioname => $radio) {
							$selected = '';
							if (! empty($customer[$formname])) {
								if ($radioname == $customer[$formname])
									$selected = 'checked="checked"';
							} else {
								if ($radio['selected'] == 1)
									$selected = 'checked="checked"';
							}
							$out .= '<input type="radio" name="'.$formnameGET.'" value="'.$radioname.'" '.$selected.' />'.$this->getTSTitle($radio['title.']);
							$out .= '<div class="clearsingleradio"></div>';
						}
					}

					$out .= '</fieldset>';

					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;
				case 'checkbox':
					$out .= '<fieldset class="'.$cssClass.'">';

					if (count($form['info.']) > 0 && $form['info.']['useTooltip'] == '1') {
						$out .= '<legend class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</legend>';
					}
					else {
						$out .= '<legend class="title">'.$this->getTSTitle($form['title.']).'</legend>';
						if (count($form['info.'])>0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}

					if ($showHidden == 1) {

						foreach ($customer[$formname] as $singleCheckbox) {
							$out .= '<p class="yourSettings">'.$this->getTSTitle($form['checkbox.'][$singleCheckbox]['title.']).'</p>';
							$out .= '<input type="hidden" name="'.$formnameGET.'[]" value="'.$singleCheckbox.'">';
						}
					}
					else {
						foreach ($form['checkbox.'] as $radioname => $radio) {
							$selected = '';
							if (! empty($customer[$formname])) {
								foreach ($customer[$formname] as $singleCheckbox) {
									if ($radioname == $singleCheckbox)
										$selected = 'checked="checked"';
								}
							} else {
								if ($radio['checked'] == 1)
									$selected = 'checked="checked"';
							}
							if ($radio['required'] == 1)
								$required = 'required="required"';
							else
								$required = '';

							if (count($radio['info.']) > 0) {
								$title = 'title="'.$this->getTSTitle($radio['info.']).'"';
							}

							$out .= '<input type="checkbox" name="'.$formnameGET.'[]" value="'.$radioname.'" '.$title.' '.$selected.' '.$required.' />'.$this->getTSTitle($radio['title.']);
							$out .= '<div class="clearsinglecheckbox"></div>';
						}
					}

					$out .= '</fieldset>';

					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;
				case 'selector':
					$out .= '<div class="'.$cssClass.'">';

					if (count($form['info.']) > 0 && $form['info.']['useTooltip'] == '1')
						$out .= '<label class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</label>';
					else {
						$out .= '<label class="title">'.$this->getTSTitle($form['title.']).'</label>';
						if (count($form['info.'])>0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}

					if ($showHidden == 1) {
						switch ($formname) {
							case 'adultSelector':
							case 'daySelector':
								$out .= '<p class="yourSettings">'.$customer[$formname].'</p>';
								break;
							default:
								$out .= '<p class="yourSettings">'.$this->getTSTitle($form['option.'][$customer[$formname]]['title.']).'</p>';
						}
						$out .= '<input type="hidden" name="'.$formnameGET.'" value="'.$customer[$formname].'">';

					} else {
						$selected='selected="selected"';
						$out .= '<select name="'.$formnameGET.'" size="1">';
						switch($formname) {
							case 'adultSelector':
								if (isset($this->lConf['adultSelector']))
									if ($this->lConf['adultSelector'] > $product['capacitymax'])
										$seladultSelector[$product['capacitymax']] = $selected;
									else if ($this->lConf['adultSelector'] < $product['capacitymin'])
										$seladultSelector[$product['capacitymin']] = $selected;
									else
										$seladultSelector[$this->lConf['adultSelector']] = $selected;
								else
									$seladultSelector[2] = $selected;

								/* how many persons are possible? */
								for ($i = $product['capacitymin']; $i<=$product['capacitymax']; $i++) {
									$out.='<option '.$seladultSelector[$i].' value='.$i.'>'.$i.' </option>';
								}
								break;
							case 'childSelector':
								break;
							case 'teenSelector':
								break;
							case 'daySelector':
								if (isset($this->lConf['daySelector']))
									$seldaySelector[$this->lConf['daySelector']] = $selected;
								else
									$seldaySelector[2] = $selected;

								for ($i = $product['minimumStay']; $i <= $product['maxAvailable']; $i += $product['daySteps']) {
										$endDate = strtotime('+'.$i.' day', $this->lConf['startDateStamp']);
										$out.='<option '.$seldaySelector[$i].' value='.$i.'>'.$i.' ('.date($this->lConf['dateFormat'], $endDate).')</option>';
								}
								break;
							default:
								foreach ($form['option.'] as $optionname => $option) {

									$out.='<option value='.$optionname.'>'.trim($this->getTSTitle($option['title.'])).' </option>';
								}
								break;
						}

						$out .= '</select>';
					}
					$out .= '</div>';

					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;
				case 'textarea':
					$out .= '<div class="'.$cssClass.'">';

					if (count($form['info.'])>0 && $form['info.']['useTooltip'] == '1')
						$out .= '<label class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</label>';
					else {
						$out .= '<label class="title">'.$this->getTSTitle($form['title.']).'</label>';
						if (count($form['info.'])>0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}

					if ($showHidden == 1) {
						$out .= '<p class="yourSettings">'.$customer[$formname].'</p>';
						$out .= '<input type="hidden" name="'.$formnameGET.'" value="'.$customer[$formname].'">';

					}
					else
						$out .= '<textarea name='.$formnameGET.' cols="50" rows="'.(int)($form['size']/50).'" wrap="PHYSICAL" '.$required.'>'.$customer[$formname].'</textarea>';
					$out .= '</div>';

					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;
				case 'infobox':
					$out .= '<div class="'.$cssClass.'">';

					if (count($form['info.'])>0 && $form['info.']['useTooltip'] == '1')
						$out .= '<p class="title" title="'.$this->getTSTitle($form['info.']).'">'.$this->getTSTitle($form['title.']).'</p>';
					else {
						$out .= '<p class="title">'.$this->getTSTitle($form['title.']).'</p>';
						if (count($form['info.'])>0)
							$out .= '<p class="info">'.$this->getTSTitle($form['info.']).'</p>';
					}

					$out .= '</div>';
					if ($form['clearAfterThis'] == '1')
						$out .= '<div class="clear"></div>';

					break;
				default:
					break;
			}
		}
		return $out;
	}

	/**
	 * Request Formular
	 * The customer enters his personal data and submits the form.
	 *
	 * @param	[type]		$conf: ...
	 * @param	integer		$stage of booking process
	 * @param	[type]		$stage: ...
	 * @return	HTML		form with booking details
	 */
	public function printUserForm($stage) {

		$interval = array();
		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
		$customer = $this->lConf['customerData'];

		// first check errors...
		if (empty($product)) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		}

		if (empty($this->lConf['form'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noFormDefined').'</b></h2>';
			return $content;
		}

		$interval['startDate'] = $this->lConf['startDateStamp'];
		$interval['endDate'] = $this->lConf['endDateStamp'];
		$interval['startList'] = strtotime('-2 day', $interval['startDate']);
		$interval['endList'] = strtotime('+2 day', $interval['startDate']);

		if ($stage > 1) {
			$numErrors = tx_abbooking_form::formVerifyUserInput();
			if ($stage == 2 && $numErrors > 0)
					$stage = 1;
			else
					$stage = 3;
			if ($stage == 3 && $numErrors > 0)
					$stage = 2;
		}

		$content .= tx_abbooking_div::printBookingStep($stage);
		switch ($stage) {
			case '1':
				$cssStep="step1";
				break;
			case '3':
				$cssStep="step2";
				break;
			case '4':
				$cssStep="step3";
				break;
		}

		$content .='<div class="requestForm '.$cssStep.'">';

		$content .='<h3>'.htmlspecialchars($this->pi_getLL('title_request')).' '.$product['detailsRaw']['header'].'</h3>';

		$content .= '<p class=available><b>'.$this->pi_getLL('result_available').'</b>';
		$content .= ' '.strftime('%A, %x', $this->lConf['startDateStamp']) . ' - ';
		$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);
		$content .= ' '.strftime('%A, %x', $availableMaxDate);
		$content .= '</p><br />';

		// show calendars following TS settings
		if ($this->lConf['form']['showCalendarMonth'] > 0) {
			if (intval($this->lConf['form']['showMonthsBeforeStart'])>0)
				$intval['startDate'] = strtotime('-'.$this->lConf['form']['showMonthsBeforeStart'].' months', $interval['startDate']);
			else
				$intval['startDate'] = $interval['startDate'];

			$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarMonth'].' months', $intval['startDate']);
			$content .= tx_abbooking_div::printAvailabilityCalendarDiv($this->lConf['ProductID'],  $intval, $this->lConf['form']['showCalendarMonth'], 0);

		} else if ($this->lConf['form']['showCalendarWeek'] > 0) {
			$intval['startDate'] = $interval['startDate'];
			$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarWeek'].' weeks', $interval['startDate']);
			$content .= tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID'], $intval);
		} else
			$content .= tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID'], $interval);


		$selected = 'selected="selected"';
		if (isset($this->lConf['adultSelector']))
			if ($this->lConf['adultSelector'] > $product['capacitymax'])
				$seladultSelector[$product['capacitymax']] = $selected;
			else if ($this->lConf['adultSelector'] < $product['capacitymin'])
				$seladultSelector[$product['capacitymin']] = $selected;
			else
				$seladultSelector[$this->lConf['adultSelector']] = $selected;
		else
			$seladultSelector[2] = $selected;

		if (isset($this->lConf['daySelector']))
			$seldaySelector[$this->lConf['daySelector']] = $selected;
		else
			$seldaySelector[2] = $selected;

		$contentError = '';
		/* handle errors */
		if (isset($this->form_errors['vacancies'])) {
			$ErrorVacancies='class="error"';
			$contentError.='<li>'.$this->form_errors['vacancies'].'</li>';
		}
		if (isset($this->form_errors['vacancies_limited'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['vacancies_limited'].'</li>';
		}
		if (isset($this->form_errors['startDateInThePast'])) {
			$ErrorVacancies='class="error"';
			$contentError.='<li>'.$this->form_errors['startDateInThePast'].'</li>';
		}
		if (isset($this->form_errors['endDateNotValid'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['endDateNotValid'].'</li>';
		}
		if (isset($this->form_errors['daySelectorNotValid'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['daySelectorNotValid'].'</li>';
		}

		if ($product['minimumStay'] > $product['maxAvailable']) {
			$ErrorVacanciesLimited='class="error"';
			if ($product['minimumStay'] == 1)
				$text_periods = ' '.$this->pi_getLL('period');
			else
				$text_periods = ' '.$this->pi_getLL('periods');

			$contentError.='<li>'.sprintf($this->pi_getLL('error_minimumStay'), $product['minimumStay'].' '.$text_periods).'</li>';
		}

		if (!empty($contentError)) {
			$content.='<div class="errorForm">';
			$content.='<ul>';
			$content.= $contentError;
			$content.='</ul>';
			$content.='</div>';
		}

		/* handle stages */
		if ($stage == 3) {
			$content.='<div class="noteForm"><p>'.htmlspecialchars($this->pi_getLL('please_confirm')).'</p></div>';

			$SubmitButtonEdit=htmlspecialchars($this->pi_getLL('submit_button_edit'));
			$SubmitButton=htmlspecialchars($this->pi_getLL('submit_button_final'));

			$content .= '<form  class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">';
			$content .= tx_abbooking_form::printUserFormElements(0, $showHidden = 1);
			$content .= $this->printCalculatedRates($product['uid'], $this->lConf['daySelector'], 1);

			$params_united = $this->lConf['startDateStamp'].'_'.$this->lConf['daySelector'].'_'.$this->lConf['adultSelector'].'_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage);
			$params = array (
				$this->prefixId.'[ABx]' => $params_united,
			);


			$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
			$content .= '<input type="hidden" name="'.$this->prefixId.'[abnocache]" value="1">';
			$content .= '<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING">
							<div class="buttons">
							<input class="edit" type="submit" name="'.$this->prefixId.'[submit_button_edit]" value="'.$SubmitButtonEdit.'">
							<input class="submit_final" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.$SubmitButton.'">
							</div>
				</form>';

		} else {
			$SubmitButton=htmlspecialchars($this->pi_getLL('submit_button_check'));

			$content .= '<form  class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">';
			$content .= tx_abbooking_form::printUserFormElements($numErrors, $showHidden = 0);
			$content .= $this->printCalculatedRates($product['uid'], $this->lConf['daySelector'], 1);

			$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage + 1);
			$params = array (
				$this->prefixId.'[ABx]' => $params_united,
			);

			$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
			$content .= '<input type="hidden" name="'.$this->prefixId.'[abnocache]" value="1">';
			$content .=	'<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING"><br/>
						<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.$SubmitButton.'">
				</form>';

		}
		$content .= '</div>';
		return $content;
	}



	/*
	 * Checks the form data for validity
	 *
	 * @return	amount		of errors found
	 */
	function formVerifyUserInput() {

		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
		$this->form_errors = array();
		$numErrors = 0;
		$dns_ok = 0;

		if (empty($this->lConf['productDetails'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		} else {
			foreach ( $this->lConf['productDetails'] as $key => $val ) {
				$product = $val;
			}
		}
		$customer = $this->lConf['customerData'];

		foreach ($this->lConf['form'] as $formname => $form) {
			$formname = str_replace('.', '', $formname);

			// skip settings which are no form fields
			if (!is_array($form))
				continue;

			switch ($formname) {
				case 'email':
					if ($form['required'] == 1) {
						// Email mit Syntax und Domaincheck
						$motif1="#^[[:alnum:]]([[:alnum:]\._-]{0,})[[:alnum:]]";
						$motif1.="@";
						$motif1.="[[:alnum:]]([[:alnum:]\._-]{0,})[\.]{1}([[:alpha:]]{2,})$#";

						if (preg_match($motif1, $customer['email'])){
							list($user, $domain)=preg_split('/@/', $customer['email'], 2);
							$dns_ok=checkdnsrr($domain, "MX");
						}
						if (!$dns_ok || !t3lib_div::validEmail($customer['email'])){
							$this->lConf['form'][$formname.'.']['error'] = is_array($form['errorText.']) ? $this->getTSTitle($form['errorText.']) : $this->pi_getLL('error_email');
							$numErrors++;
						}
					}
					break;
				case 'checkinDate':
					if ($form['required'] == 1) {
						if ($product['prices'][$this->lConf['startDateStamp']]['checkInOk'] == '0') {
							$this->lConf['form'][$formname.'.']['error'] .= $this->pi_getLL('error_no_checkIn_on').' '.strftime('%a, %x', $this->lConf['startDateStamp']);
							$numErrors++;
						}
						if ($this->lConf['startDateStamp'] < (time()-86400)) {
							$this->lConf['form'][$formname.'.']['error'] .= $this->pi_getLL('error_startDateInThePast');
							$numErrors++;
						}
						if (empty($customer[$formname])) {
							$this->lConf['form'][$formname.'.']['error'] .= is_array($form['errorText.']) ? $this->getTSTitle($form['errorText.']) : $this->pi_getLL('error_required');
							$numErrors++;
						}
					}
					break;
				case 'daySelector':
					if ($form['required'] == 1) {
						if (empty($customer[$formname]) || $customer[$formname] == 0) {
							$this->lConf['form'][$formname.'.']['error'] = is_array($form['errorText.']) ? $this->getTSTitle($form['errorText.']) : $this->pi_getLL('error_daySelectorNotValid');
							$numErrors++;
						}
					}
					break;

				default:
					if ($form['required'] == 1 && empty($customer[$formname])) {
						$this->lConf['form'][$formname.'.']['error'] = is_array($form['errorText.']) ? $this->getTSTitle($form['errorText.']) : $this->pi_getLL('error_required');
						$numErrors++;
					}

					break;
			}
		}

		// check for limited vacancies...
		if ($product['maxAvailable'] < $this->lConf['daySelector']) {
			$form_errors['vacancies_limited'] = $this->pi_getLL('error_vacancies_limited');
			$numErrors++;
		}

		return $numErrors;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_form.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_form.php']);
}
?>
