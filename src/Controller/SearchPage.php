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
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$terms = explode(',',$hmp_elastic['elastic_filters']);
		$output = '';
		$output .= "<form id='elastic-search-form'><input id='elastic-search-terms' type='text' name='search-terms'>";
		if($terms[0] != '') {
			$output .= "<select id='elastic-filter-terms'>";
			$output .= '<option value="">--Select--</option>';
			foreach($terms as $term) {
				$option = explode('|',$term);
				$output .= "<option value='" . $option[0] . "'>" . $option[1] . "</option>";
			}
			$output .= "</select>";
		}
		$output .= "<input type='button' name='submit' value='Search' id='elastic-search-submit'>";
		$output .="</form>";
		return array(
			'#type' => 'markup',
			'#markup' => t("<h1>Search Page</h1>$output<div id='elastic-search-results'></div>"),
		);
	}

	public function searchResults() {
		$page = $_GET['page'];
		$query = $_GET['query'];
		$query_terms = $_GET['query_terms'];
		$results = $this->grabContent($page,$query,$query_terms);
		$output = $this->formatContent($results,$page);
		echo $output;exit;
	}

	/*
	* Format content
	*/
	public function formatContent($data,$page) {
		$items = $data->hits;
		$content = "<div id='elastic-results'><ul id='search-results'>";
		$count = $page * 15;
		foreach($items as $item) {
			$content .= '<li class="search-results">';
			$content .= '<div class="search-results-subhead">' . $item->_source->subhead . '</div>';
			$content .= '<div class="search-results-title"><a target="_blank" href="' . $item->_source->url . '">' . $item->_source->title . '</a></div>';
			$content .= '<div class="search-results-site"><a target="_blank" href="http://' . $item->_source->domain . '">' . $item->_source->site_name . '</a></div>';
			$content .= '<div class="search-results-summary">' . $item->_source->summary . '</div>';
			$content .= '</li>';
			$count++;
			if($count >= $data->total)
				break;
		}
		$content .= '</ul>';
		$content .= "<div id='elastic-page'>";
		if($page > 0)
			$content .= '<div id="elastic-pager-previous">Previous</div>';
		if($data->total > $count)
			$content .= '<div id="elastic-pager-next">Next</div>';
		$content .= "<div id='elastic-pager-count'>$count/" . $data->total . " results</div>";
		$content .= "</div></div>";

		
		return $content;
	}

		/*
	 * Index the content to Elasticsearch
	 */
	public function grabContent($page,$query,$query_terms) {
		$hmp_elastic = \Drupal::state()->get('hmp_elastic');
		$url = $hmp_elastic['elastic_server'];
		$username = $hmp_elastic['elastic_username'];
		$password = $hmp_elastic['elastic_password'];

		$index = $hmp_elastic['elastic_index'];
		$doc_type = 'default';
		$port = 443;
		$json_array = array(
			'from' => $page,
			'size' => 15,
			'query' => array(
				'function_score' => array(
					'query' => array(
						'bool' => array(
							'must' => array(
								/*array(
									'multi_match' => array(	
										'query' => $query,
										'fields' => ['title','body','terms'],
										'operator' => 'and'
									)
								),*/
								array(
									'match' => array(
										'status' => '1'
									)
								)
							)
							/*'should' => array(
								array(
									'match' => array(
										'terms' => array(
											'query' => 'article',
											'boost' => 1
										)
									)
								)
							)*/
						)
					),
					'functions' => array(
						array(
							'filter' => array(
								'range' => array(
									'created' => array(
										'gte' => 'now-4y',
										'lte' => 'now'
									)
								)
							),'weight' => 15
						),
						array(
							'filter' => array(
								'range' => array(
									'created' => array(
										'gte' => 'now-5y',
										'lte' => 'now-4y'
									)
								)
							),'weight' => 14
						),
						array(
							'filter' => array(
								'range' => array(
									'created' => array(
										'gte' => 'now-7y',
										'lte' => 'now-5y'
									)
								)
							),'weight' => 7
						)
				))
			)
		);
		if($query != '') 
			$json_array['query']['function_score']['query']['bool']['must'][] = array(
				'multi_match' => array(	
					'query' => $query,
					'fields' => ['title','body','terms'],
					'operator' => 'and'
				)
			);
		if($query_terms !== '') {
			$json_array['query']['function_score']['query']['bool']['must'][] = array(
				'match' => array(
					'terms' => $query_terms
				)
			);
		}
		$json_doc = json_encode($json_array);

	//	echo $json_doc;exit;
	    $baseUri = $url.'/'.$index.'/'.$doc_type.'/_search';

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
	    $data = json_decode($response);
	   //echo '<pre>';print_r($data);exit;
	    return $data->hits;
	}
}
