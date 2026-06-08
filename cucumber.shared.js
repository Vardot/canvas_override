// Shared cucumber-js worldParameters for both Drupal flavours.
//
// `cucumber.js` (Drupal Standard) and `cucumber.drupalcms.js` (Drupal CMS)
// import this and layer their own report / screenshot / video paths on
// top so artefacts from the two suites do not collide.

module.exports = {
  launchUrl: process.env.LAUNCH_URL || 'http://localhost',
  // Test users for each Drupal Standard role.
  //
  // On Drupal Standard, the Webmaster row is the `drush site:install`
  // super-admin created with `--account-name=webmaster --account-pass=…`.
  // On Drupal CMS, the installer profile ignores those flags and creates
  // uid 1 as `admin`; the CI before_script renames uid 1 to `webmaster`
  // with the matching password so the same registry is reused.
  users: {
    Webmaster: {
      username: 'webmaster',
      email: 'webmaster@example.test',
      password: 'dD.123123ddd',
      isAdmin: true,
    },
    'Content editor': {
      username: 'content_editor_user',
      email: 'content_editor_user@example.test',
      password: 'dD.123123ddd',
      roles: ['content_editor'],
    },
    'Authenticated user': {
      username: 'authenticated_user',
      email: 'authenticated_user@example.test',
      password: 'dD.123123ddd',
      roles: [],
    },
    // One user per Canvas Override permission tier (roles created by the
    // canvas_override_roles_test_base recipe). These drive the access matrix:
    // who may see the Canvas tab / Reset tab / Edit template tab / operation
    // link, and who is denied the routes.
    'Canvas admin': {
      username: 'canvas_admin_user',
      email: 'canvas_admin_user@example.test',
      password: 'dD.123123ddd',
      roles: ['canvas_admin'],
    },
    'Canvas global': {
      username: 'canvas_global_user',
      email: 'canvas_global_user@example.test',
      password: 'dD.123123ddd',
      roles: ['canvas_global'],
    },
    'Canvas per-bundle': {
      username: 'canvas_bundle_user',
      email: 'canvas_bundle_user@example.test',
      password: 'dD.123123ddd',
      roles: ['canvas_bundle'],
    },
    'Canvas reset': {
      username: 'canvas_reset_user',
      email: 'canvas_reset_user@example.test',
      password: 'dD.123123ddd',
      roles: ['canvas_reset'],
    },
    'Canvas template': {
      username: 'canvas_template_user',
      email: 'canvas_template_user@example.test',
      password: 'dD.123123ddd',
      roles: ['canvas_template'],
    },
  },
  // The custom "Marketing campaign" node that the CI before_script seeds with
  // Canvas Override enabled on its content type. Features reach it through this
  // alias so they never have to hard-code a node id. Using a custom content
  // type (not article/page) exercises Canvas Override the way a site builder
  // would enable it on their own content type.
  canvasOverride: {
    bundle: process.env.CANVAS_OVERRIDE_BUNDLE || 'marketing_campaign',
    bundleLabel:
      process.env.CANVAS_OVERRIDE_BUNDLE_LABEL || 'Marketing campaign',
    nodeAlias:
      process.env.CANVAS_OVERRIDE_NODE_ALIAS || '/canvas-override-test',
    // The seeded campaign the @setup feature creates through the browser. Held
    // here (not hard-coded inside the step) so a single place defines the
    // fixture content.
    nodeTitle:
      process.env.CANVAS_OVERRIDE_NODE_TITLE ||
      'Canvas Override Marketing Campaign',
    nodeTag: process.env.CANVAS_OVERRIDE_NODE_TAG || 'Spring Sale',
    nodeBody:
      process.env.CANVAS_OVERRIDE_NODE_BODY ||
      'Join our Spring Sale campaign. Limited-time offers across the store, ' +
        'composed on the shared Marketing campaign Canvas layout.',
    // A second content type the setup feature enables through the browser to
    // exercise the content-type form flow end to end.
    formBundle: process.env.CANVAS_OVERRIDE_FORM_BUNDLE || 'page',
    formBundleLabel:
      process.env.CANVAS_OVERRIDE_FORM_BUNDLE_LABEL || 'Basic page',
  },
  minWaitTime: {
    page: 3000,
    before_scenario: 0,
    after_scenario: 0,
    before_step: 0,
    after_step: 0,
  },
  selectors: {
    css: {},
    xpath: {},
    filesPath: './tests/selectors/',
    files: [
      'cms-drupal-core-claro.json',
      'cms-drupal-cms-gin.json',
      'canvas_override.json',
    ],
    offset: 60,
    breakpoints: {
      xs: { width: 375, height: 667 },
      sm: { width: 576, height: 800 },
      md: { width: 768, height: 1024 },
      lg: { width: 992, height: 768 },
      xl: { width: 1200, height: 900, default: true },
      xxl: { width: 1400, height: 900 },
    },
  },
  screenshot: {
    dir: './tests/screenshots',
    purge: false,
    onFailed: true,
    onEveryStep: false,
    alwaysFullscreen: false,
    failedPrefix: 'failed_',
    filenamePattern: '{datetime}.{feature_file}.feature_{step_line}.{ext}',
    filenamePatternFailed:
      '{failed_prefix}{datetime}.{feature_file}.feature_{step_line}.{ext}',
    infoTypes: '',
  },
  video: {
    mode: 'on-failure',
    dir: './tests/videos',
    size: { width: 1280, height: 720 },
    filenamePattern: '{datetime}.{feature_file}.{scenario}.{status}.{ext}',
  },
  javascript: {
    mode: 'warn',
    levels: ['error'],
    ignore: '',
    beforeScenario: false,
    afterScenario: true,
  },
};
