<?php

namespace Drupal\link_shortner;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 */
class LinkShortnerRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Save an entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('link_shortner')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('link_shortner')
        ->fields($entry)
        ->condition('pid', $entry['pid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('Update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]
      ), 'error');
    }
    return $count ?? 0;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
    $this->connection->delete('link_shortner')
      ->condition('pid', $entry['pid'])
      ->execute();
  }

  public function getByPID( $pid) {
      $database = \Drupal::database();
      $query = $database->query("SELECT * FROM {link_shortner} where pid=$pid");
      $qeuryResult = $query->fetchAll();//[0];
      if (count($qeuryResult) > 0){
          return $qeuryResult[0];
      }
      return null;  
  }

  public function getByShortURL( $id) {
    $query = "SELECT * FROM {link_shortner} WHERE short_URL = '" . "/l/" . $id ."'"  ;
    $database = \Drupal::database();
    $query = $database->query($query);
    $qeuryResult = $query->fetchAll();//[0];
    if (count($qeuryResult) > 0){
        return $qeuryResult[0];
    }
    return null;  
  }

  /**
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function load(array $entry = []) {
    $select = $this->connection
      ->select('link_shortner')
      ->fields('link_shortner');

    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }

    return $select->execute()->fetchAll();
  }

  /**
   */
  public function advancedLoad() {
    // Get a select query for our dbtng_example table. We supply an alias of e
    // (for 'example').
    $select = $this->connection->select('link_shortner', 'e');
    // Join the users table, so we can get the entry creator's username.
    $select->join('users_field_data', 'u', 'e.uid = u.uid');
    // Select these specific fields for the output.
    $select->addField('e', 'pid');
    $select->addField('u', 'name', 'username');
    $select->addField('e', 'URL');
    $select->addField('e', 'short_URL');
    $select->addField('e', 'link_description');
    // Filter only persons named "John".
    //$select->condition('e.name', 'John');
    // Filter only persons older than 18 years.
    //$select->condition('e.age', 18, '>');
    // Make sure we only get items 0-49, for scalability reasons.
    //$select->range(0, 50);

    $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $entries;
  }
}
