<?php
/**
 * Created by PhpStorm.
 * User: fcavallo
 * Date: 03/07/18
 * Time: 17.01
 */

namespace Drupal\store_locator_import\Service;


use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

class UserCountryService
{
    public function getAvailableCountries(User $user) {
        $userCountries = [];
        foreach ($user->get('field_country')->getValue() as $c) {
            $userCountries[] = $c['value'];
        }

        return $userCountries;
    }
    public function getAvailableBrands(User $user) {
        $userBrands = [];

        foreach ($user->get('field_brands')->getValue() as $c) {

            $term = Term::load($c['target_id']);
            $name = $term->getName();
            $userBrands[$term->get('field_codice_identificativo')->getValue()[0]['value']] = $name;

        }

        return $userBrands;
    }
}