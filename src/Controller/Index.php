<?php

namespace Drupal\hmp_elastic\Controller;

use \Drupal\Core\Controller\ControllerBase;
use \Drupal\Core\Routing\TrustedRedirectResponse;
use \Drupal\node\Entity\Node;
use \Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use \Drupal\paragraphs\Entity\Paragraph;

class Index extends ControllerBase {

	/**
	 * Landing page for Indexing action
	 **/
	public function indexPage() {
		\Drupal::service('page_cache_kill_switch')->trigger();

		return array(
			'#type' => 'markup',
			'#markup' => t('<h1>Index Site</h1><div id="percent"></div><div id="ajax-results"></div>'),
		);
	}

	/**
	 * Performs index process, sends data to elastic
	 **/
	public function index($offset,$qty) {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$max = 500;
		if($offset <= $max) {
			$output = array(
				'status' => 0,
				'offset' => $offset+=$qty,
			);
		} else {
			$output = array(
				'status' => 1,
			);
		}
		echo json_encode(array($output));exit;
	}

}
?>