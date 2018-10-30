<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

class pageDom{
	private $link = "";
	private $page = 1;
	private $document = null;

	public function __construct($link){
		$this->link = $link;
	}

	public function loadDocument(){
		$options = array(
		    'http' => array(
		        'user_agent' => 'PHP libxml agent',
		    )
		);

		$context = stream_context_create($options);
		libxml_set_streams_context($context);

		$document = new DOMDocument();

		$pageHTML = file_get_contents( str_replace("#page#", $this->page, $this->link) );

		// request a file through HTTP
		libxml_use_internal_errors(true);
		$document->loadHTML($pageHTML);
		libxml_use_internal_errors(false);

		$this->document = $document;
	}

	public function loadAllLinksFromDocument(){
		/*** remove silly white space ***/
	    $this->document->preserveWhiteSpace = false;
	     
	    /*** get the links from the HTML ***/
	    $links = $this->document->getElementsByTagName('a');

	    return $links;
	}

	public function filterLinks($links){
		$filtered = [];
		foreach ($links as $link) {
			preg_match('/mieten\/\d+$/', $link->getAttribute('href'), $matched);
			if(count($matched) > 0){
				$filtered[end($matched)] =  array(
					"url" => $link->getAttribute('href'),
					"title" => $link->childNodes->item(0)->nodeValue
				);
			}
		}

		return $filtered;
	}

	public function loadFilteredLinks(){
		$links = $this->loadAllLinksFromDocument();
		return $this->filterLinks($links);
	}

	public function getMaxPage(){
		$finder = new DomXPath($this->document);
		$classname="paginator-counter";
		$nodes = $finder->query("//*[contains(@class, '$classname')]/span");
		
		return $nodes->item(1)->nodeValue;
	}

	public function loadAllPages(){
		ini_set('max_execution_time', 100000000000);
		$allLinks = [];

		//load first page
		$this->loadDocument();
		$allLinks = $this->loadFilteredLinks();
		$lastPage = $this->getMaxPage();

		if($lastPage > 1){
			for($page=2;$page <= $lastPage;$page++){
				$this->page = $page;
				$this->loadDocument();
				$allLinks = array_merge($allLinks, $this->loadFilteredLinks());
			}
		}
		
		return $allLinks;
	}

}

try{
	$pageDom = new pageDom("https://www.homegate.ch/mieten/immobilien/kanton-zuerich/trefferliste?ep=#page#");
	$links = $pageDom->loadAllPages();
	print_r($links);
}catch(Exception $e){
	echo $e->getMessage();
}