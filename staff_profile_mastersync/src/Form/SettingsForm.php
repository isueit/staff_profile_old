<?php

namespace Drupal\staff_profile_mastersync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\Entity\EncryptionProfile;

/*
 * Class SettingsForm
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
   protected function getEditableConfigNames() {
     return [
       'staff_profile_sync.settings',
     ]; }

   /**
    * {@inheritdoc}
    */
    public function getFormID() {
      return 'settings_form';
    }

    /**
     * {@inheritdoc}
     */
     public function buildForm(array $form, FormStateInterface $form_state) {
       $config = $this->config('staff_profile_sync.settings');
       $site_vars = \Drupal::config('system.site');
       $form['run_on_chron'] = [
         '#type' => 'checkbox',
         '#title' => t('Run sync on next Chron'),
         '#default_value' => false,
         '#description' => t('Check this and save the settings to run profile sync on the next chron')
       ];
      $form['run_on_save'] = [
        '#type' => 'checkbox',
        '#title' => t('Run sync on form Submit'),
        '#default_value' => false,
        '#description' => t('Check this and save the settings to run profile sync')
      ];
      if ($config->get('staff_profile_sync_last')>0) {
        $form['last_run'] = [
          '#markup' => 'Last Updated on: ' . $config->get('staff_profile_sync_last'),
        ];
      }
      $form['username'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#description' => $this->t('The username used to connect to the staff database.'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $config->get('db_username'),
      );
      $form['password'] = array(
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#description' => $this->t('The password used to connect to the staff database. Note: This is not a secure password storage facility, use an account with the fewest permissions. This field will always show up blank even when a password is saved.'),
        '#size' => 64,
        '#default_value' => $this->t(""),
      );
      $form['server_url'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('URL'),
        '#description' => $this->t('The url used to connect to the staff database.'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $config->get('db_address'),
      );
      $form['database'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Database'),
        '#description' => $this->t('The database containing the staff profiles.'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $config->get('db_database'),
      );

      $form['smugmug_pwd'] = array(
        '#type' => 'password',
        '#title' => $this->t('Smugmug Password'),
        '#description' => $this->t('The password to unlock the SmugMug Staff Portrait Album. This is not a secure data storage. This field will appear blank even with a password saved.'),
        '#size' => 64,
        '#default_value' => $this->t(""),
      ); //TODO do we want to use different encryption profile?
      $form['smugmug_api'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Smugmug Api Key'),
        '#description' => $this->t('Smug Mug API key. This will appear blank even when there is a saved api key'),
        '#size' => 64,
        '#default_value' => $this->t(""),
      );

      // List of encryption profiles for selector
      $encrypt_ids = \Drupal::entityQuery('encryption_profile')->execute();
      $encrypt_list = array();
      foreach ($encrypt_ids as $eid) {
        $encrypt_prof = \Drupal::entityTypeManager()->getStorage('encryption_profile')->load($eid);
        $encrypt_list[$eid] = $encrypt_prof->label();
      }

      $form['encrypt_profile'] = array(
        '#type' => 'select',
        '#title' => $this->t("Encryption Profile"),
        '#description' => $this->t('Encryption profile used for encryption.'),
        '#options' => $encrypt_list,
        '#default_value' => $config->get('sync_encrypt_profile'),
      );


      return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      parent::validateForm($form, $form_state);

      $config = $this->config('staff_profile_sync.settings');
      $saved_pwd = $config->get('db_password');
      $new_pwd = $form_state->getValue('password');
      $encrypt_profile = EncryptionProfile::load($form_state->getValue('encrypt_profile'));

      if (empty($new_pwd)) {
        $form_state->setValue('password', $saved_pwd);
      } else {
        $form_state->setValue('password', \Drupal::service('encryption')->encrypt($new_pwd, $encrypt_profile));
      }

      $saved_pwd_smug = $config->get('smug_mug_password');
      $new_pwd_smug = $form_state->getValue('smugmug_pwd');
      if (empty($new_pwd_smug)) {
        $form_state->setValue('smugmug_pwd', $saved_pwd_smug);
      } else {
        $form_state->setValue('smugmug_pwd', \Drupal::service('encryption')->encrypt($new_pwd_smug, $encrypt_profile));
      }

      $saved_api_smug = $config->get('smug_mug_api_key');
      $new_api_smug = $form_state->getValue('smugmug_api');
      if (empty($new_api_smug)) {
        $form_state->setValue('smugmug_api', $saved_api_smug);
      } else {
        $form_state->setValue('smugmug_api', \Drupal::service('encryption')->encrypt($new_api_smug, $encrypt_profile));
      }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      parent::submitForm($form, $form_state);
      //If checked, run sync
      if ($form_state->getValue('run_on_save')) {
        staff_profile_sync_updater();
      }
      //Set time of last sync to zero so it runs on next chron
      if ($form_state->getValue('run_on_chron')) {
        $this->config('staff_profile_sync.settings')
          ->set('staff_profile_sync_last', 0);
      }
      $this->config('staff_profile_sync.settings')
        ->set('db_username', $form_state->getValue('username'))
        ->set('db_password', $form_state->getValue('password'))
        ->set('db_address', $form_state->getValue('server_url'))
        ->set('db_database', $form_state->getValue('database'))
        ->set('smug_mug_password', $form_state->getValue('smugmug_pwd'))
        ->set('smug_mug_api_key', $form_state->getValue('smugmug_api'))
        ->set('sync_encrypt_profile', $form_state->getValue('encrypt_profile'))
        ->save();

  }
}
