<?php

namespace Drupal\link_shortner\Form;
// for info look at dbtng in the drupal examples

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_shortner\LinkShortnerRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sample UI to update a record.
 */
class LinkShortnerUpdateForm extends FormBase {

  /**
   * Our database repository service.
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_shortner_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('link_shortner.repository'));
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Construct the new form object.
   */
  public function __construct(LinkShortnerRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * Sample UI to update a record.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Wrap the form in a div.
    $form = [
      '#prefix' => '<div id="updateform">',
      '#suffix' => '</div>',
    ]; 

    // Add some explanatory text to the form.
    $form['link_description'] = [
      '#markup' => $this->t('Demonstrates a database update operation.'),
    ];

    // Query for items to display.
    $entries = $this->repository->load();
    
    // Tell the user if there is nothing to display.
    if (empty($entries)) {
      $form['no_values'] = [
        '#value' => $this->t('No entries exist in the table link_shortner table.'),
      ];
      return $form;
    }

    $keyed_entries = [];
    $options = [];
    foreach ($entries as $entry) {
      $options[$entry->pid] = $this->t('@pid: @URL - @link_description', [
        '@pid' => $entry->pid,
        '@URL' => $entry->URL,
        '@link_description' => $entry->link_description,
      ]);
      $keyed_entries[$entry->pid] = $entry;
    }

    // Grab the pid.
    $pid = $form_state->getValue('pid');
    // Use the pid to set the default entry for updating.
    $default_entry = !empty($pid) ? $keyed_entries[$pid] : $entries[0];

    // Save the entries into the $form_state. We do this so the AJAX callback
    // doesn't need to repeat the query.
    $form_state->setValue('entries', $keyed_entries);

    $form['pid'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Choose entry to update'),
      '#default_value' => $default_entry->pid,
      '#ajax' => [
        'wrapper' => 'updateform',
        'callback' => [$this, 'updateCallback'],
      ],
    ];

    $form['URL'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Updated URL'),
      '#size' => 15,
      '#default_value' => $default_entry->URL,
    ];

    $form['link_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Updated link description'),
      '#size' => 255,
      '#default_value' => $default_entry->link_description,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * AJAX callback handler for the pid select.
   *
   * When the pid changes, populates the defaults from the database in the form.
   */
  public function updateCallback(array $form, FormStateInterface $form_state) {
    // Gather the DB results from $form_state.
    $entries = $form_state->getValue('entries');
    // Use the specific entry for this $form_state.
    $entry = $entries[$form_state->getValue('pid')];
    // Setting the #value of items is the only way I was able to figure out
    // to get replaced defaults on these items. #default_value will not do it
    // and shouldn't.
    foreach (['URL', 'short_URL'] as $item) {
      $form[$item]['#value'] = $entry->$item;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //dtw do something here
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather the current user so the new record has ownership.
    $account = $this->currentUser();
    // Save the submitted entry.
    $entry = [
      'pid' => $form_state->getValue('pid'),
      'URL' => $form_state->getValue('URL'),
      'link_description' => $form_state->getValue('link_description'),
      'uid' => $account->id(),
    ];
    $count = $this->repository->update($entry);
    $this->messenger()->addMessage($this->t('Updated entry @entry (@count row updated)', [
      '@count' => $count,
      '@entry' => print_r($entry, TRUE),
    ]));
  }
}