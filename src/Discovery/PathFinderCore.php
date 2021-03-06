<?php

/**
 * @file
 * Contains Drupal\Composer\ClassLoader\Discovery\PathFinderCore.
 */

namespace Drupal\Composer\ClassLoader\Discovery;

use Drupal\Composer\ClassLoader\ClassLoaderException;

class PathFinderCore extends PathFinderBase implements PathFinderInterface {

  /**
   * {@inheritdoc}
   */
  public function find($seed) {
    $seed = realpath($seed);
    // Try to create the iterator with the seed.
    try {
      $directory = new \DirectoryIterator($seed);
    }
    catch (\UnexpectedValueException $e) {
      // If the seed was not a directory, then get the parent directory.
      $path_info = pathinfo($seed);
      $directory = new \DirectoryIterator($path_info['dirname']);
    }
    do {
      if ($this->isDrupalRoot($directory)) {
        return $this->cleanDirPath($directory->getPathName()) . $this->path;
      }
    }
    while ($directory = $this->getParentDirectory($directory));
    throw new ClassLoaderException(sprintf('Drupal core directory could not be found as a parent of: %s.', $seed));
  }

  /**
   * Checks if the passed directory is the Drupal root.
   *
   * @param \DirectoryIterator $directory
   *   The directory iterator item.
   *
   * @return bool
   *   TRUE if the passed directory is the Drupal root.
   */
  protected function isDrupalRoot(\DirectoryIterator $directory) {
    // We need to clone the $directory object to avoid modifying its internal
    // operator.
    $d = clone $directory;
    // Check if there is a COPYRIGHT.txt file in the directory.
    foreach ($d as $item) {
      if (!$item->isFile() || $item->getFilename() != 'COPYRIGHT.txt') {
        continue;
      }
      $line = fgets(fopen($item->getPathname(), 'r'));
      return strpos($line, 'All Drupal code is Copyright') === 0;
    }
    return FALSE;
  }

  /**
   * Gets the parent directory iterator.
   *
   * @param \DirectoryIterator $directory
   *   The current directory iterator.
   *
   * @return \DirectoryIterator
   *   The parent directory.
   */
  protected function getParentDirectory(\DirectoryIterator $directory) {
    $path_name = $directory->getPathname();
    $path_name = $this->cleanDirPath($path_name);

    $path_info = pathinfo($path_name);
    if (!empty($path_info['dirname'])) {
      try {
        return new \DirectoryIterator($path_info['dirname']);
      }
      catch (\UnexpectedValueException $e) {}
    }
    return NULL;
  }

}
