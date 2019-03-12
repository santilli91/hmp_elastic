<?php

namespace Drupal\hmp_elastic\Controller;

use \Drupal\Core\Controller\ControllerBase;
use \Drupal\Core\Routing\TrustedRedirectResponse;
use \Drupal\node\Entity\Node;
use \Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use \Drupal\paragraphs\Entity\Paragraph;

class SearchPage extends ControllerBase {

	/**
	 * Landing page for Indexing action
	 **/
	public function searchPage() {
		\Drupal::service('page_cache_kill_switch')->trigger();

		$output = '';

		$output .= "<form id='search-form'><input id='search-terms' type='text' name='search-terms'>
		<input type='submit' name='submit' value='Search'></form>";

		return array(
			'#type' => 'markup',
			'#markup' => t("<h1>Search Page</h1>$output"),
		);
	}
}
