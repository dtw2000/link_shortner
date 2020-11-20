<?php

namespace Drupal\link_shortner\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\link_shortner\LinkShortnerRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use \Drupal\Core\Cache\CacheableMetadata;

class LinkShortnerController extends ControllerBase {

  protected $repository;

  public static function create(ContainerInterface $container) {
    $controller = new static($container->get('link_shortner.repository'));
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  public function __construct(LinkShortnerRepository $repository) {
    $this->repository = $repository;
  }

  public function redirectByID(String $id) {
    $entry = $this->repository->getByShortURL( $id);

    if ( is_null( $entry )){
      return array(
        '#type' => 'markup',
        '#markup' => '<p>Sorry, please return to <a href="/links">/links</a>.</p>',
        '#title' => ' This doesn\'t appear to be a valid redirect',
        '#cache' => [
			    'max-age' => 0,   // x in seconds, 0 - disables caching
			  ],
      );
    }

    $entry->redirectCount = $entry->redirectCount+1;
    //update by 1
    $this->repository->update(get_object_vars($entry));

    //not exactly sure about this
    \Drupal::service('page_cache_kill_switch')->trigger(); 
    return (new TrustedRedirectResponse($entry->URL))
      ->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
  }

  public function redirectInfoPage(String $id) {
    $entry = $this->repository->getByShortURL($id);
    if ( is_null( $entry )){
      return array(
        '#type' => 'markup',
        '#markup' => '<p>Sorry, please return to <a href="/links">/links</a>.</p>',
        '#title' => ' This doesn\'t appear to be a valid redirect',
        '#cache' => [
			    'max-age' => 0,   // x in seconds, 0 - disables caching
			  ],
      );
    }
      
    // $baseURL = \Drupal::request()->getHost();
    $baseURL = \Drupal::request()->getSchemeAndHttpHost();
    $qr = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $baseURL . $entry->short_URL;

    return array(
			'#type' => 'markup',
			'#markup' => 'This is the code',
      '#URL' => $entry->URL,
      '#short_URL' => $entry->short_URL,
      '#qr' => $qr,
      '#redirectCount' => $entry->redirectCount,
      '#link_description' =>$entry->link_description,
			'#theme' => 'link_shortner_info_page',
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
