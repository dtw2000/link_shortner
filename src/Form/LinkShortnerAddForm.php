<?php

namespace Drupal\link_shortner\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\link_shortner\LinkShortnerRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form to add a database entry, with all the interesting fields.
 */
class LinkShortnerAddForm implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   */
  protected $repository;

  /**
   * The current user.
   *
   * We'll need this service in order to check if the user is logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('link_shortner.repository'),
      $container->get('current_user')
    );
    // The StringTranslationTrait trait manages the string translation service
    // for us. We can inject the service here.
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Construct the new form object.
   */
  public function __construct(LinkShortnerRepository $repository, AccountProxyInterface $current_user) {
    $this->repository = $repository;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_shortner_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['add'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Get a shorter URL'),
    ];

    $form['add']['URL'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#required' => TRUE, //make this field required
      '#size' => 255,
    ];

    $form['add']['link_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tell us about your link!'),
      '#size' => 255,
      '#required' => TRUE, //make this field required
     // '#description' => $this->t("Values greater than 127 will cause an exception. Try it - it's a great example why exception handling is needed with DTBNG."),
    ];
    
    $form['add']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get a URL alias!'),
    ];

    return $form;

  }

  private function _custom_node_form_submit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $nid =  $form_state->getValue('nid');
    return $nid;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      // $form_state->setError($form['add'], $this->t('You must be logged in to add values to the database.'));
    }
    
    $url = trim($form_state->getValue('URL'));
    $url = trim($url, '!"#$%&\'()*+,-./@:;<=>[\\]^_`{|}~');
    // $regex = "@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS";
    $regex = "@(https?)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$@iS";
    if (preg_match($regex, $url)) {
      ;//$x = "debug";
    } else {
      $form_state->setError($form['add'], $this->t('Your URL doesn\'t seem to be valid'));
    }
  }

  public function getRandomString(){
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $length =  rand (5 , 9 );
    $result = '';// substr(str_shuffle($permitted_chars), 0, $length);
    for ($x = 0; $x < $length; $x++) {
      $result = $result . substr(str_shuffle($permitted_chars), 0, 1);
    }    

    $entry = $this->repository->getByShortURL($result);

    if ( is_null( $entry )){
      return $result;
    }
    return $this->getRandomString();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather the current user so the new record has ownership.
    $account = $this->currentUser;
    $short_URL = $this->getRandomString(); // $this->repository->maxPid() + 1;

    // Save the submitted entry.
    $entry = [
      'URL' => trim($form_state->getValue('URL')),
      'link_description' => trim($form_state->getValue('link_description')),
      'uid' => $account->id(),
      'short_URL' => "/l/" . $short_URL,
      //'nid' => $nid,
    ];

    $return = $this->repository->insert($entry);

    if ($return) {
      $this->messenger()->addMessage($this->t('Your link has been created, Thank you!' ));

      $path = "/l/view/" . $short_URL ;
      // // query string
      // $path_param = ['abc' => '123', 'xyz' => '456' ];
      $url = Url::fromUserInput($path);
      // $url = Url::fromUserInput($path, ['query' => $path_param]);
      $form_state->setRedirectUrl($url);
    }
  }
}
