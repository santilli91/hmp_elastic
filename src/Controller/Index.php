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
			'#markup' => t('<h1 id="index-start">Index Site</h1><div id="percent"></div><div id="ajax-results"></div>'),
		);
	}

	/**
	 * Performs index process, sends data to elastic
	 **/
	public function index($offset,$qty) {
		\Drupal::service('page_cache_kill_switch')->trigger();
		$query = $this->getQuery();
		$max = $this->getCount($query);
		$data = $this->getData($query,$offset,$qty);
		if($offset <= $max) {
			$output = array(
				'status' => 0,
				'offset' => $offset+=$qty,
				'max' => $max,
				'data' => $data
			);
		} else {
			$output = array(
				'status' => 1,
				'offset' => $offset+=$qty,
				'max' => $max,
			);
		}
		echo json_encode(array($output));exit;
	}

	public function getQuery() {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$types = explode(',',$hmp_elastic['elastic_content_type']);
		$query = \Drupal::database()->select('node_field_data','n');
		$query->fields('n',['title','nid','created']);
		$query->condition('n.type',$types,'IN');
		return $query;
	}

	public function getCount($query) {
		$results = $query->execute()->fetchAll();
		$count = count($results);
		return $count;
	}

	public function getData($query,$offset,$qty) {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$query->range($offset,$qty);
		$results = $query->execute();
		$nodes = array();
		foreach($results as $result) {
			$node = Node::load($result->nid);
			$fields = explode(',',$hmp_elastic['elastic_term']);
			$terms = $_SERVER['HTTP_HOST'];
			foreach($fields as $field) {
				if($field != '' && $node->hasField("$field")) {
					$items = $node->get("$field")->getValue();
					foreach($items as $item) {
						$term = Term::load($item['target_id']);
						if($term) 
							$terms .= ',' . $term->getName();
					}
				}
			}

			$nodes[] = array(
				array(
					'index' => array(
						'_index' => $hmp_elastic['elastic_index'],
						'_id' => $_SERVER['HTTP_HOST'] . ':' . $result->nid,
					)
				),
				array(
					'title' => $result->title,
					'created' => $result->created,
					'url' => 'https://' . $_SERVER['HTTP_HOST'] . \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$result->nid),
					'summary' => '',
					'body' => $terms,
				)
			);
		}
		$this->sendIndex($hmp_elastic,$nodes);
		return $nodes;
	}

	/*
	 * Index the content to Elasticsearch
	 */
	function sendIndex($hmp_elastic,$nodes) {
		$url = $hmp_elastic['elastic_server'];
		$username = $hmp_elastic['elastic_username'];
		$password = $hmp_elastic['elastic_password'];

		$index = $hmp_elastic['elastic_index'];
		$doc_type = 'default';
		$port = 443;
		$items = array();
		foreach($nodes as $node) {
			foreach($node as $line) {
				$items[] = json_encode($line);
			}
		}
		$json_doc = join("\n", $items) . "\n";

	    $baseUri = $url.'/'.$index.'/'.$doc_type.'/_bulk';

	    $ci = curl_init();
	    curl_setopt($ci, CURLOPT_URL, $baseUri);
	    curl_setopt($ci, CURLOPT_PORT, $port);
	    curl_setopt($ci, CURLOPT_TIMEOUT, 200);
	    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
	    curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($ci, CURLOPT_POSTFIELDS, $json_doc);
	    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	    curl_setopt($ci, CURLOPT_USERPWD, $username . ":" . $password);
	    $response = curl_exec($ci);
	    print_r($response);
	}
}
?>