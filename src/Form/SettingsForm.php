<?php
// src/Form/SettingsForm.php

namespace Drupal\mikedelta_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class SettingsForm extends ConfigFormBase {

  /**
   * O serviço de mensagens.
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constrói o SettingsForm.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * A fábrica de configurações.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * O serviço de mensagens.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

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

      $has_image = isset($gallery_items[$i]['image_fid']) && !empty($gallery_items[$i]['image_fid']);
      $title = $has_image ? $this->t('Imagem @num ✔ (Preenchido)', ['@num' => $i + 1]) : $this->t('Imagem @num', ['@num' => $i + 1]);

      $form['image_settings']['gallery_items'][$i] = [
        '#type' => 'details',
        '#title' => $title,
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

      if ($has_image) {
        $form['image_settings']['gallery_items'][$i]['actions']['clear'] = [
          '#type' => 'submit',
          '#value' => $this->t('Limpar este slot'),
          '#name' => 'clear_button_' . $i,
          '#submit' => ['::clearSlotSubmit'],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--danger']],
        ];
      }
    }
     
    return parent::buildForm($form, $form_state);
  }
  

  public function clearSlotSubmit(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    $button_name_parts = explode('_', $triggering_element['#name']);
    $index_to_clear = end($button_name_parts);

    $user_input = &$form_state->getUserInput();
    unset($user_input['gallery_items'][$index_to_clear]);

    $config = $this->configFactory()->getEditable('mikedelta_popup.settings');
    $gallery_items = $config->get('gallery_items');

    unset($gallery_items[$index_to_clear]);

    $gallery_items = array_values($gallery_items);
    
    $config->set('gallery_items', $gallery_items)->save();

    $this->messenger->addStatus($this->t('O slot de Imagem @num foi limpo.', ['@num' => (int)$index_to_clear + 1]));
  
    $form_state->setRebuild(TRUE);
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

    $cache_url = Url::fromRoute('system.performance_settings')->toString();

    $warning_message = $this->t('As alterações podem não ser visíveis imediatamente devido ao sistema de cache. <a href=":cache_url">Limpe todos os caches</a> para garantir que as novas configurações sejam aplicadas.', [
      ':cache_url' => $cache_url,
    ]);

    $this->messenger->addStatus($this->t('As configurações do MikeDelta Popup foram salvas com sucesso.'));
    $this->messenger->addWarning($warning_message);
  }
}