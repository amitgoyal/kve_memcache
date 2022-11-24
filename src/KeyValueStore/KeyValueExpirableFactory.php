<?php

namespace Drupal\kve_memcache\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\kve_memcache\KeyValueStore\MemcacheStorageExpirable;
use Drupal\memcache\DrupalMemcacheInterface;
use Drupal\memcache\MemcacheBackendFactory;
use Drupal\memcache\Driver\MemcacheDriverFactory;

/**
 * A cache backend factory responsible for the construction of redis cache bins.
 */
class KeyValueExpirableFactory implements KeyValueFactoryInterface {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * List of collections.
   *
   * @var array
   */
  protected $collections = [];

  protected $memcacheFactory;
  /**
   * Creates a redis KeyValueExpirableFactory.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   The redis client factory.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   */
  public function __construct(MemcacheDriverFactory $memcacheFactory, SerializationInterface $serializer) {
    $this->memcacheFactory = $memcacheFactory;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      $this->storages[$collection] = new MemcacheStorageExpirable($collection, $this->serializer, $this->memcacheFactory->get('data'));
    }
    return $this->storages[$collection];
    //return $this->collections[$collection];
  }

}
