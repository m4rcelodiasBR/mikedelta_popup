<?php
// src/Form/SettingsForm.php

namespace Drupal\mikedelta_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class SettingsForm extends ConfigFormBase {

  const MAX_IMAGES = 10;

  public function getFormId() {
    return 'mikedelta_popup_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['mikedelta_popup.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mikedelta_popup.settings');

    // Configurações Globais
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Configurações Gerais'),
      '#open' => TRUE,
    ];

    $form['general_settings']['popup_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ativar o popup no site'),
      '#default_value' => $config->get('popup_enabled'),
    ];

    $form['general_settings']['activation_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Data de início da exibição'),
      '#default_value' => $config->get('activation_start_date'),
      '#description' => $this->t('Deixe em branco para começar imediatamente.'),
    ];

    $form['general_settings']['activation_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Data de fim da exibição'),
      '#default_value' => $config->get('activation_end_date'),
      '#description' => $this->t('Deixe em branco para nunca expirar.'),
    ];

    $form['popup_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tipo de Popup'),
      '#options' => [
        'text' => $this->t('Apenas Texto'),
        'image' => $this->t('Apenas Imagem'),
      ],
      '#default_value' => $config->get('popup_type') ?: 'text',
      '#required' => TRUE,
    ];

    // Configurações para Popup TEXTO
    $form['text_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Configurações do Popup de Texto'),
      '#open' => TRUE,
      '#states' => ['visible' => [':input[name="popup_type"]' => ['value' => 'text']]],
    ];

    $form['text_settings']['popup_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Conteúdo do Popup'),
      '#format' => $config->get('popup_text.format') ?: 'basic_html',
      '#default_value' => $config->get('popup_text.value'),
    ];

    // Configurações para Popup IMAGEM
    $form['image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Configurações do Popup de Imagem'),
      '#open' => TRUE,
      '#states' => ['visible' => [':input[name="popup_type"]' => ['value' => 'image']]],
    ];

    $gallery_items = $config->get('gallery_items') ?: [];

    $form['image_settings']['gallery_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Imagens da Galeria (Máximo de @count)', ['@count' => self::MAX_IMAGES]),
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < self::MAX_IMAGES; $i++) {
      $form['image_settings']['gallery_items'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Imagem @num', ['@num' => $i + 1]),
      ];

      $form['image_settings']['gallery_items'][$i]['image_fid'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload da Imagem'),
        '#upload_location' => 'public://mikedelta_popup/gallery',
        '#upload_validators' => ['file_validate_extensions' => ['gif png jpg jpeg']],
        '#default_value' => isset($gallery_items[$i]['image_fid']) ? [$gallery_items[$i]['image_fid']] : [],
      ];

      $form['image_settings']['gallery_items'][$i]['link_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link para esta imagem'),
        '#default_value' => $gallery_items[$i]['link_url'] ?? '',
      ];
      
      $form['image_settings']['gallery_items'][$i]['link_target'] = [
        '#type' => 'radios',
        '#title' => $this->t('Abrir link em'),
        '#options' => ['_self' => $this->t('Mesma janela'), '_blank' => $this->t('Nova janela')],
        '#default_value' => $gallery_items[$i]['link_target'] ?? '_self',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // Imagem Única
    $image_fid = $form_state->getValue(['popup_image_upload', 0]);
    if (!empty($image_fid)) {
      $file = File::load($image_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Múltiplas Imagens
    $images_fids = $form_state->getValue('popup_images_upload');
    if (!empty($images_fids)) {
      foreach ($images_fids as $fid) {
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();
        }
      }
    }

    $config = $this->configFactory()->getEditable('mikedelta_popup.settings');
    $gallery_values = $form_state->getValue('gallery_items');

    $saved_gallery_items = [];
    if (!empty($gallery_values)) {
      foreach ($gallery_values as $item) {
        // Apenas salva o item se uma imagem foi enviada
        if (!empty($item['image_fid'][0])) {
          $fid = $item['image_fid'][0];
          $file = File::load($fid);
          if ($file) {
            $file->setPermanent();
            $file->save();
          }
          
          $saved_gallery_items[] = [
            'image_fid' => $fid,
            'link_url' => $item['link_url'],
            'link_target' => $item['link_target'],
          ];
        }
      }
    }

    $config
      ->set('popup_enabled', $form_state->getValue('popup_enabled'))
      ->set('activation_start_date', $form_state->getValue('activation_start_date'))
      ->set('activation_end_date', $form_state->getValue('activation_end_date'))
      ->set('popup_type', $form_state->getValue('popup_type'))
      ->set('popup_text', $form_state->getValue('popup_text'))
      ->set('gallery_items', $saved_gallery_items)
      ->clear('image_mode')
      ->clear('popup_image_upload')
      ->clear('popup_image_url')
      ->clear('popup_images_upload')
      ->clear('rotation_mode')
      ->clear('rotation_speed')
      ->clear('popup_link_url')
      ->clear('popup_link_target')
      ->save();

    parent::submitForm($form, $form_state);
  }
}