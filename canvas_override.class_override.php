<?php

/**
 * @file
 * Option 2 (issue #3621557): run without the Canvas #3567225 patch.
 *
 * Canvas ships Drupal\canvas\Storage\ComponentTreeLoader as a `final` class,
 * and several Canvas services type-hint that concrete class, so Canvas Override
 * cannot substitute its per-content loader without either the #3567225 patch
 * (Option 1) or this replacement.
 *
 * This file is Composer `files`-autoloaded, so it runs at the very start of
 * every request - before the class is used and before the service container is
 * built. It registers a PREPENDED autoloader for the single class name
 * Drupal\canvas\Storage\ComponentTreeLoader that, ONLY when the installed
 * Canvas still declares that class as `final` (i.e. unpatched), loads the
 * module's un-finalised copy (canvas-compat/ComponentTreeLoader.php) under that
 * class name instead of Canvas's own file. The class is then extendable, so the
 * normal Canvas Override machinery (the ComponentTreeLoader subclass swapped in
 * by CanvasOverrideServiceProvider) works exactly as on a patched site.
 *
 * On patched Canvas - or any Canvas whose ComponentTreeLoader is not `final` -
 * the autoloader stands aside and Canvas's real class is loaded, so patched
 * sites are unaffected and never run a stale copy.
 *
 * This file is deliberately procedural: it must run before Drupal registers the
 * module's PSR-4 namespace, so it cannot rely on autoloading its own classes.
 *
 * @see https://www.drupal.org/project/canvas_override/issues/3621557
 */

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/**
 * The Canvas class this replacement occupies when Canvas is unpatched.
 */
const CANVAS_OVERRIDE_COMPONENT_TREE_LOADER = 'Drupal\\canvas\\Storage\\ComponentTreeLoader';

/**
 * Resolves the path to Canvas's own ComponentTreeLoader file.
 *
 * @return string|null
 *   The absolute path, or NULL when it cannot be resolved.
 */
function canvas_override_find_canvas_component_tree_loader(): ?string {
  foreach (spl_autoload_functions() ?: [] as $callback) {
    if (is_array($callback) && ($callback[0] ?? NULL) instanceof ClassLoader) {
      $file = $callback[0]->findFile(CANVAS_OVERRIDE_COMPONENT_TREE_LOADER);
      if (is_string($file) && $file !== '' && is_file($file)) {
        return $file;
      }
    }
  }
  return NULL;
}

/**
 * Loads the module's copy under Canvas's class name, on unpatched Canvas only.
 *
 * @param string $class
 *   The class being autoloaded.
 */
function canvas_override_autoload_component_tree_loader(string $class): void {
  if ($class !== CANVAS_OVERRIDE_COMPONENT_TREE_LOADER) {
    return;
  }

  $real = canvas_override_find_canvas_component_tree_loader();
  // Canvas is not installed, or its file cannot be resolved: defer to the
  // normal autoloader.
  if ($real === NULL) {
    return;
  }

  $source = @file_get_contents($real);
  // Only step in when Canvas ships the class as final (unpatched). Otherwise
  // return without loading anything, so Canvas's real class is used.
  if ($source === FALSE || preg_match('/\bfinal\s+class\s+ComponentTreeLoader\b/', $source) !== 1) {
    return;
  }

  // Load the module's un-finalised copy, which declares the class under
  // Canvas's own namespace. Once declared, PHP will not load Canvas's file.
  require __DIR__ . '/canvas-compat/ComponentTreeLoader.php';
}

/**
 * Registers the unpatched-Canvas ComponentTreeLoader replacement autoloader.
 */
function canvas_override_register_component_tree_loader_replacement(): void {
  static $registered = FALSE;
  if ($registered) {
    return;
  }
  $registered = TRUE;

  // If Canvas's class is already loaded this request there is nothing to do
  // (and nothing to fix if it was the real class).
  if (class_exists(CANVAS_OVERRIDE_COMPONENT_TREE_LOADER, FALSE)) {
    return;
  }

  spl_autoload_register('canvas_override_autoload_component_tree_loader', TRUE, TRUE);
}

canvas_override_register_component_tree_loader_replacement();
