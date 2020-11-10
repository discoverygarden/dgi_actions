<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Data Profile setting entities.
 */
interface DataProfileInterface extends ConfigEntityInterface {

    /**
     * Gets the set Entity type.
     *
     * @return string
     */
    public function getEntity();

    /**
     * Gets the set Bundle type.
     *
     * @return string
     */
    public function getBundle();

    /**
     * Gets the set Data Profile Type.
     *
     * @return string
     */
    public function getDataprofile();

    /**
     * Gets the set Data.
     *
     * @return array
     */
    public function getData();

    /**
     * Sets the set Data.
     *
     * @param array
     */
    public function setData(array $data);

}
