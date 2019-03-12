<?php

namespace Drupal\hmp_elastic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigForm extends FormBase {
	public function buildForm(array $form, FormStateInterface $form_state) {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		
		$form['info'] = array(
			'#type' => 'item',
			'#markup' => '<hr><h2>ElasticSearch Information</h2>',
		);
		$form['elastic_server'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_elastic['elastic_server'],
			'#title' => 'ElasticSearch Server: ',
		);
		$form['elastic_index'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_elastic['elastic_index'],
			'#title' => 'ElasticSearch Index: ',
		);
		$form['elastic_username'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_elastic['elastic_username'],
			'#title' => 'ElasticSearch Username: ',
		);
		$form['elastic_password'] = array(
			'#type' => 'textfield',
			'#default_value' => $hmp_elastic['elastic_password'],
			'#title' => 'ElasticSearch Password: ',
		);
		$form['elastic_content_type'] = array(
			'#type' => 'textarea',
			'#default_value' => $hmp_elastic['elastic_content_type'],
			'#title' => 'ElasticSearch Content Type (comma separated): ',
		);
		$form['elastic_term'] = array(
			'#type' => 'textarea',
			'#default_value' => $hmp_elastic['elastic_term'],
			'#title' => 'ElasticSearch Taxonomy (field machine name) (comma separated): ',
		);
		$form['elastic_node'] = array(
			'#type' => 'textarea',
			'#default_value' => $hmp_elastic['elastic_node'],
			'#title' => 'ElasticSearch node reference (field_machine_name) (comma separated): ',
		);
		$form['elastic_body'] = array(
			'#type' => 'textarea',
			'#default_value' => $hmp_elastic['elastic_body'],
			'#title' => 'ElasticSearch body field machine name (if nested in paragraph, separate with pipe: paragraph|field) ',
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
		$hmp_elastic = array(
			'elastic_server'			=>	$form_state->getValue('elastic_server'),
			'elastic_index'				=>	$form_state->getValue('elastic_index'),
			'elastic_username'				=>	$form_state->getValue('elastic_username'),
			'elastic_password'				=>	$form_state->getValue('elastic_password'),
			'elastic_content_type'				=>	$form_state->getValue('elastic_content_type'),
			'elastic_term'				=>	$form_state->getValue('elastic_term'),
			'elastic_node'				=>	$form_state->getValue('elastic_node'),
			'elastic_body'				=>	$form_state->getValue('elastic_body')
		);
		\Drupal::state()->set('hmp_elastic',$hmp_elastic);
	}



}
