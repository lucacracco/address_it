<?php

namespace Drupal\address_it\EventSubscriber;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Drupal\address\Event\SubdivisionsEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AddressEventSubscriber.
 *
 * @package Drupal\address_it\EventSubscriber
 */
class AddressEventSubscriber implements EventSubscriberInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * AddressEventSubscriber constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = ['onAddressFormat'];
    $events[AddressEvents::SUBDIVISIONS][] = ['onSubdivisions'];
    return $events;
  }

  /**
   * Alters the address format.
   *
   * @param \Drupal\address\Event\AddressFormatEvent $event
   *   The address format event.
   */
  public function onAddressFormat(AddressFormatEvent $event) {
    $definition = $event->getDefinition();
    $definition['format'] = "%givenName %familyName\n%organization\n%addressLine1\n%addressLine2\n%postalCode %administrativeArea %locality";
    $definition['subdivision_depth'] = 2;
    $event->setDefinition($definition);
  }

  /**
   * Provides the subdivisions for Italian: "Province -> Comuni".
   *
   * @param \Drupal\address\Event\SubdivisionsEvent $event
   *   The subdivisions event.
   */
  public function onSubdivisions(SubdivisionsEvent $event) {
    $parents = $event->getParents();

    // Managed only IT.
    if ($parents[0] != 'IT') {
      return;
    }

    // Use a original definitions for Provinces.
    if (count($parents) == 1 && $definitions = $this->getOriginalDefinitions($parents)) {
      $event->setDefinitions($definitions);
      return;
    }

    $base_path = drupal_get_path('module', 'address_it');
    $file = $base_path . DIRECTORY_SEPARATOR . "data/comuni.json";

    $cache_key = 'address.subdivisions.IT.locality';
//    if ($cached = $this->cache->get($cache_key)) {
//      $subdivisions = $cached->data;
//    }
//    else
      if ($raw_definition = @file_get_contents($file)) {
      $source = json_decode($raw_definition, TRUE);
      $subdivisions = [];

      // Create groups with "provincia->comuni".
      foreach ($source as $local_data) {
        $subdivisions[$local_data['sigla']][$local_data['nome']] = [];
      }

      // Sort each group for AZ.
      foreach ($subdivisions as $prov => &$data) {
        ksort($data);
      }
      $this->cache->set($cache_key, $subdivisions, CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
    }

    if (empty($subdivisions)) {
      return;
    }

    $definitions = [
      'country_code' => $parents[0],
      'parents' => $parents,
      'subdivisions' => $subdivisions[$parents[1]],
    ];
    $event->setDefinitions($definitions);
  }

  /**
   * Builds a group from the provided parents.
   *
   * @param array $parents
   *   The parents (country code, subdivision codes).
   *
   * @return array|NULL
   *   The subdivision definitions.
   *
   * @see \Drupal\address\Repository\SubdivisionRepository::loadDefinitions().
   */
  protected function getOriginalDefinitions(array $parents): ?array {

    $countryCode = array_shift($parents);
    $group = strtoupper($countryCode);

    // Use a original file from CommerceGuy.
    $reflector = new \ReflectionClass(SubdivisionRepository::class);
    $file = dirname($reflector->getFileName()) . '/../../resources/subdivision/' . $group . '.json';

    $cache_key = 'address.subdivisions.' . $group;
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }
    elseif ($raw_definition = @file_get_contents($file)) {
      $definitions = json_decode($raw_definition, TRUE);

      foreach ($definitions['subdivisions'] as $code => &$definition) {
        // Add common keys from the root level.
        $definition['country_code'] = $definitions['country_code'];
        if (isset($definitions['locale'])) {
          $definition['locale'] = $definitions['locale'];
        }
        // Ensure the presence of code and name.
        $definition['code'] = $code;
        if (!isset($definition['name'])) {
          $definition['name'] = $code;
        }
        if (isset($definition['local_code']) && !isset($definition['local_name'])) {
          $definition['local_name'] = $definition['local_code'];
        }

        $definition['has_children'] = TRUE;
      }
      $definitions['parents'] = [$group];

      $this->cache->set($cache_key, $definitions, CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      return $definitions;
    }
    return NULL;
  }

}
