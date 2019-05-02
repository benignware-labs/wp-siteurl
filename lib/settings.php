<?php

add_action( 'admin_init', function() {
  register_setting( 'siteurl-options-group', 'siteurl_options', array(
    'default' => array(
      'environment' => array(
        'development' => 1,
        'production' => 1
      )
    )
  ));
});


add_action('admin_menu', function() {
  add_options_page( __('Site Url', 'siteurl'), __('Site Url', 'siteurl'), 'manage_options', 'siteurl', 'siteurl_options_page');
});

function siteurl_options_page() {
  $options = wp_siteurl_get_options();

  ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h1><?= __('Settings'); ?> › <?= __('Site url', 'siteurl'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields( 'siteurl-options-group' ); ?>
        <?php do_settings_sections( 'siteurl-options-group' ); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">
              <label>Environment</label>
            </th>
            <td>
              <div>
                <label>
                  <input
                    type="checkbox"
                    name="siteurl_options[environment][development]"
                    value="1" <?php checked( $options['environment']['development'], 1 ); ?>
                  />
                  <?= __('Development', 'siteurl'); ?>
                </label>
              <div>
              <div>
                <label>
                  <input
                    type="checkbox"
                    name="siteurl_options[environment][production]"
                    value="1" <?php checked( $options['environment']['production'], 1 ); ?>
                  />
                  <?= __('Production', 'siteurl'); ?>
                </label>
              </div>
              <p class="description"><?= __('Specify environments in which to use the plugin', 'siteurl'); ?></p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
}
