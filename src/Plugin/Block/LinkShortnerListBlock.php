<?php
/**
 * @file
 */
namespace Drupal\link_shortner\Plugin\Block;
use Drupal\link_shortner\LinkShortnerRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Block\BlockBase;
//use Drupal\link_shortner\Controller\LinkShortnerController;
/**
 * Provides a 'Link Shortner List' Block
 * @Block(
 * id = "link_shortner_list_block",
 * admin_label = @Translation("Link Shortner List"),
 * )
 */
class LinkShortnerListBlock extends BlockBase {
    /**
	 * {@inheritdoc}
	 */
	public function build() {
	  	$connection = \Drupal\Core\Database\Database::getConnection();
    	$query = $connection->select('link_shortner', 't')->fields('t', array('pid','URL','short_URL','link_description','redirectCount'));
    	$query->orderBy('pid', 'DESC');

		$entries = array();
		$result = $query->execute();
    	$headers = array("link_description", "URL", "short_URL","info_page" ,"redirectCount");
    	// loop through results and store in array
		while($record = $result->fetchAssoc())
		{
			$viewLink = substr_replace($record['short_URL'], '/view', 2 , 0 );
	  		$entries[] = array(
       			'link_description' => $record["link_description"],
				'URL' => $record['URL'],
				'short_URL' => $record['short_URL'],
				'info_page' => substr_replace($record['short_URL'], '/view', 2 , 0 ),
				'redirectCount' => $record['redirectCount'],
			);
		}

		return array(
			'#type' => 'markup',
			'#markup' => 'This is the code',
			'#entries' => $entries,
			'#headers' => $headers,
			'#theme' => 'link_shortner_list',
			'#title' => '',
			'#cache' => [
			  'max-age' => 0,   // x in seconds, 0 - disables caching
			],
			'#attached' => [
				'library' => [
				  'link_shortner/custom_library', //include our custom library for this response
				]
			],
		);
	}
}
