# PHPUnit Coverage

Alongside the browser acceptance suite, canvas_override ships PHPUnit tests
under `tests/src/`.

The browser-level behaviour (the content type form, the Canvas tabs, the
per-content editor, reset, the full permission matrix, accessibility) is covered
end to end by the functional acceptance suite, so the PHPUnit layer focuses on
the parts that are best asserted in isolation.

## Unit

`tests/src/Unit/CanvasOverridePermissionsTest.php` covers the per-bundle
permission generation in isolation — that each Canvas Override-enabled content
type mints exactly the expected permissions, including for a custom content
type.

## Running

```bash
# From a Drupal core checkout with canvas_override + canvas (patched) in place
php core/scripts/run-tests.sh --group canvas_override

# Or with PHPUnit directly
vendor/bin/phpunit -c core/phpunit.xml.dist \
  --group canvas_override modules/contrib/canvas_override
```

In CI the `phpunit` job runs the `kernel` and `unit` suites in parallel, both
scoped to `--group canvas_override`.

## How the two layers divide the work

PHPUnit asserts the module's internals against a controlled kernel — exact
permission grants and registration. The acceptance suite asserts the
user-visible behaviour as a black box on a fully-built site and the real
front-end themes, where regressions in routing, caching, theming or the Canvas
integration surface that a kernel test cannot see.
