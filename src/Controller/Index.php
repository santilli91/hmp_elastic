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
		$query->range($offset,$qty);
		$results = $query->execute();
		$nodes = array();

		//Iterate nodes
		foreach($results as $result) {
			$node = Node::load($result->nid);
			$nodes[] = $this->getFields($node);
		}
		$this->sendIndex($nodes);
		return $nodes;
	}

	public function getFields($node) {
		/** Iterate through fields based on machine names for taxonomy **/
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$fields = explode(',',$hmp_elastic['elastic_term']);
		$terms = $_SERVER['HTTP_HOST'];
		foreach($fields as $field) {
			if($field != '' && $node->hasField("$field")) {
				$items = $node->get("$field")->getValue();
				foreach($items as $item) {
					$term = Term::load($item['target_id']);
					if($term) 
						$terms .= ' ' . $term->getName();
				}
			}
		}

		/** Iterate through fields based on machine name for the body content, should be text fields only **/
		$fields = explode(',',$hmp_elastic['elastic_body']);
		$body = '';
		foreach($fields as $field) {

			//In the instance of a paragraph, iterate through paragraph, the paragraphs fields
			if(strpos($field,'|')) {
				$p = explode('|',$field);
				$pField = $p[0];
				$bField = $p[1];
				if($node->hasField("$pField")) {
					$paragraphs = $node->get("$pField")->getValue();
					foreach($paragraphs as $item) {
						$paragraph = \Drupal\paragraphs\Entity\Paragraph::load( $item['target_id'] );
						if($paragraph->hasField("$bField")) {
							$contents = $paragraph->get("$bField")->getValue();
							foreach($contents as $content) {
								$body .= $content['value'];
							}
						}
					}
				}
			}
			else if($field != '' && $node->hasField("$field")) {
				$contents = $node->get("$field")->getValue();
				foreach($contents as $content) {
					$body .= $content['value'];
				}
			}
		}

		//Strip tags, build summary
		$body = strip_tags($body);
		$sum = explode('.',$body);
		$summary = implode('.',array($sum[0],$sum[1],$sum[2]));


		//Get the site name
		$config = \Drupal::config('system.site');
  		$site_name = $config->get('name');
		/**  Generate the array for each node before sending to json and elastic **/
		return array(
			array(
				'index' => array(
					'_index' => $hmp_elastic['elastic_index'],
					'_id' => $_SERVER['HTTP_HOST'] . ':' . $node->id(),
				)
			),
			array(
				'title' => $node->getTitle(),
				'created' => date('Y-m-d',$node->created->value),
				'url' => 'https://' . $_SERVER['HTTP_HOST'] . \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$node->id()),
				'summary' => $summary,
				'body' => $body . ' ' . $terms,
				'domain' => $_SERVER['HTTP_HOST'],
				'site_name' => $site_name,
				'status' => $node->status->value,
				'terms' => $terms
			)
		);

	}

	/*
	 * Index the content to Elasticsearch
	 */
	public function sendIndex($nodes) {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
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
	   // echo '<pre>';print_r(json_decode($response));exit;
	}
	/*
	 * Index the content to Elasticsearch
	 */
	public function deleteIndexedNodes() {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$url = $hmp_elastic['elastic_server'];
		$username = $hmp_elastic['elastic_username'];
		$password = $hmp_elastic['elastic_password'];

		$index = $hmp_elastic['elastic_index'];
		$doc_type = 'default';
		$port = 443;
		$json_doc = json_encode(array(
			'query' => array(
				'match' => array(
					'domain' => $_SERVER['HTTP_HOST']
				)
			)
		));

	    $baseUri = $url.'/'.$index.'/'.$doc_type.'/_delete_by_query';

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
	    echo 'complete';exit;
	}

	/*
	 * Index the content to Elasticsearch
	 */
	public function deleteIndexedNode($nid) {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$url = $hmp_elastic['elastic_server'];
		$username = $hmp_elastic['elastic_username'];
		$password = $hmp_elastic['elastic_password'];

		$index = $hmp_elastic['elastic_index'];
		$doc_type = 'default';
		$port = 443;
		$json_doc = json_encode(array(
			'query' => array(
				'match' => array(
					'_id' => $_SERVER['HTTP_HOST'].':'.$nid
				)
			)
		));

	    $baseUri = $url.'/'.$index.'/'.$doc_type.'/_delete_by_query';

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
	}
}
?>