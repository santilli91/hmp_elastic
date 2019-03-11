<?php

namespace Drupal\hmp_elastic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigForm extends FormBase {
	public function buildForm(array $form, FormStateInterface $form_state) {
		$hmp_track = \Drupal::state()->get('hmp_elastic');
		/** Woopra Info **/
		$form['info'] = array(
			'#type' => 'item',
			'#markup' => '<hr><h2>ElasticSearch Information</h2>',
		);
		$form['elastic_server'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_track['elastic_server'],
			'#title' => 'ElasticSearch Server: ',
		);
		$form['elastic_index'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_track['elastic_index'],
			'#title' => 'ElasticSearch Index: ',
		);
		

		/** Submit Button **/
		$form['break'] = array(
			'#type' => 'item',
			'#markup' => '<br><hr><br>',
		);
		$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Submit',
		);
		return $form;
	}

	public function getFormId() {
		return 'hmp_elastic_config_form';
	}


	public function submitForm(array &$form, FormStateInterface $form_state) {
		$hmp_track = array(
			'elastic_server'			=>	$form_state->getValue('elastic_server'),
			'elastic_index'				=>	$form_state->getValue('elastic_index')
		);
		\Drupal::state()->set('hmp_elastic',$hmp_track);
	}



}
