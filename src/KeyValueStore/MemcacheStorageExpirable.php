<?php

namespace Drupal\kve_memcache\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\memcache\DrupalMemcacheInterface;
use Drupal\memcache\MultipartItem;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\KeyValueStore\StorageBase;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\memcache\MemcacheBackendFactory;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class MemcacheStorageExpirable extends StorageBase implements KeyValueStoreExpirableInterface {

  protected $memcache;

  protected $serializer;
  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value_expire.
   */
  public function __construct($collection, SerializationInterface $serializer, DrupalMemcacheInterface $memcache) {
    $this->memcache = $memcache;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    $cids = [$key];
    $cache = $this->memcache->memcache->getMultiple($cids);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $cache = $this->memcache->getMulti($keys);
    $fetched = [];

    foreach ($cache as $result) {
      if (is_string($result)){
        continue;
      }

      if (!$this->memcache->memcache->timeIsGreaterThanBinDeletionTime($result->created)) {
        continue;
      }

      if ($this->memcache->memcache->valid($result->cid, $result) || $allow_invalid) {

        // If the item is multipart, rebuild the original cache data by fetching
        // children and combining them back into a single item.
        if ($result->data instanceof MultipartItem) {
          $childCIDs = $result->data->getCids();
          $dataParts = $this->memcache->getMulti($childCIDs);
          if (count($dataParts) !== count($childCIDs)) {
            // We're missing a chunk of the original entry. It is not valid.
            continue;
          }
          $result->data = $this->memcache->memcache->combineItems($dataParts);
        }

        // Add it to the fetched items to diff later.
        $fetched[$result->cid] = $result;
      }
    }

    // Remove items from the referenced $cids array that we are returning,
    // per comment in Drupal\Core\Cache\CacheBackendInterface::getMultiple().
    $cids = array_diff($keys, array_keys($fetched));

    return $fetched;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    
  }

  /**
   * Saves a value for a given key with a time to live.
   *
   * This will be called by setWithExpire() within a try block.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  protected function doSetWithExpire($key, $value, $expire) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpire($key, $value, $expire) {
    //kint($value);exit;
    // $tags[] = "memcache:$this->memcache->bin";
    // $tags = array_unique($tags);
    // // Sort the cache tags so that they are stored consistently.
    // sort($tags);

    // Create new cache object.
    // $cache = new \stdClass();
    // $cache->cid = $key;
    // $cache->data = $value;
    // $cache->created = round(microtime(TRUE), 3);
    // $cache->expire = $expire;
    // $cache->tags = $tags;
    // $cache->checksum = $this->memcache->checksumProvider->getCurrentChecksum($tags);

    // // Cache all items permanently. We handle expiration in our own logic.
    // if ($this->memcache->set($cid, $cache)) {
    //   return TRUE;
    // }

    // // Assume that the item is too large.  We need to split it into multiple
    // // chunks with a parent entry referencing all the chunks.
    // $childKeys = [];
    // foreach ($this->memcache->memcache->splitItem($cache) as $part) {
    //   // If a single chunk fails to be set, stop trying - we can't reconstitute
    //   // a value with a missing chunk.
    //   if (!$this->memcache->memcache->set($part->cid, $part)) {
    //     return FALSE;
    //   }
    //   $childKeys[] = $part->cid;
    // }

    // // Create and write the parent entry referencing all chunks.
    // $cache->data = new MultipartItem($childKeys);
    // return $this->memcache->memcache->set($cid, $cache);
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpireIfNotExists($key, $value, $expire) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $cid => $item) {
      $item += [
        'expire' => -1,
        'tags' => [],
      ];

      $this->memcache->set($cid, $item['data'], $item['expire'], $item['tags']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $cid) {
      $this->memcache->delete($cid);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $newKey) {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
  }
}
