<?php

/**
 * @file
 * Contém o formulário de configuração para o módulo MikeDelta Popup.
 */

namespace Drupal\mikedelta_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Define o formulário de configurações para o MikeDelta Popup.
 *
 * Este formulário permite aos administradores gerenciar todas as configurações
 * do pop-up, incluindo ativação, tipo de conteúdo e a galeria de pop-ups
 * rotativas. Ele também fornece atalhos para páginas de ajuda e gerenciamento
 * de arquivos.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * O serviço de mensagens do Drupal.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constrói o objeto do formulário SettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * A fábrica de configurações do Drupal.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   * O serviço de mensagens para exibir notificações ao usuário.
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

  /**
   * O número máximo de imagens permitidas na galeria.
   *
   * @var int
   */
  const MAX_IMAGES = 10;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mikedelta_popup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mikedelta_popup.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mikedelta_popup.settings');

    $form['shortcuts'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mikedelta-popup-shortcuts', 'clearfix']],
      '#weight' => -100,
    ];

    // Botão para a Página de Ajuda
    $form['shortcuts']['help_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Ajuda do Módulo'),
      '#url' => Url::fromRoute('help.page', ['name' => 'mikedelta_popup']),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'float: right; margin-left: 10px;',
      ],
    ];

    // Botão para a Administração de Arquivos
    $form['shortcuts']['files_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Gerenciar Arquivos'),
      '#url' => Url::fromRoute('view.files.page_1'),
      '#attributes' => [
        'class' => ['button'],
        'style' => 'float: right;',
      ],
    ];

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
        'text' => $this->t('Tipo Texto'),
        'image' => $this->t('Tipo Imagem'),
      ],
      '#default_value' => $config->get('popup_type') ?: 'text',
      '#required' => TRUE,
    ];

    // Popup TEXTO
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

    // IMAGEM
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
      '#description' => $this->t('Você poderá inserir até 10 imagens (10 Popups) nesta galeria. Cada uma redirecionando para um link diferente. Os popups serão rotacionados automaticamente a cada sessão aberta pelo usuário na ordem crescente.'),
    ];

    for ($i = 0; $i < self::MAX_IMAGES; $i++) {

      $date_path = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
      $upload_path = 'public://mikedelta_popup/popups/' . $date_path;

      $has_image = (isset($gallery_items[$i]['image_fid']) && !empty($gallery_items[$i]['image_fid'])) || !empty($gallery_items[$i]['image_external_url']);
      $title = $has_image ? $this->t('Imagem @num ✔ (Preenchido)', ['@num' => $i + 1]) : $this->t('Imagem @num', ['@num' => $i + 1]);

      $form['image_settings']['gallery_items'][$i] = [
        '#type' => 'details',
        '#title' => $title,
      ];

      $form['image_settings']['gallery_items'][$i]['image_source_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Fonte da Imagem'),
        '#options' => [
          'upload' => $this->t('Fazer Upload'),
          'external' => $this->t('URL Externa'),
        ],
        '#default_value' => $gallery_items[$i]['image_source_type'] ?? 'upload',
      ];

      $form['image_settings']['gallery_items'][$i]['upload_container'] = [
          '#type' => 'container',
          '#states' => ['visible' => [':input[name="gallery_items[' . $i . '][image_source_type]"]' => ['value' => 'upload']]],
      ];

      $form['image_settings']['gallery_items'][$i]['upload_container']['image_fid'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Arquivo da Imagem'),
        '#upload_location' => $upload_path,
        '#upload_validators' => ['file_validate_extensions' => ['gif png jpg jpeg']],
        '#default_value' => isset($gallery_items[$i]['image_fid']) ? [$gallery_items[$i]['image_fid']] : [],
      ];

      $form['image_settings']['gallery_items'][$i]['external_container'] = [
        '#type' => 'container',
        '#states' => ['visible' => [':input[name="gallery_items[' . $i . '][image_source_type]"]' => ['value' => 'external']]],
      ];

      $form['image_settings']['gallery_items'][$i]['external_container']['image_external_url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL da Imagem Externa'),
        '#default_value' => $gallery_items[$i]['image_external_url'] ?? '',
        '#maxlength' => 512,
        '#description' => $this->t('Permite URLs longas, utilize preferencialmente URLs .mb ou .mil.br'),
      ];

      $form['image_settings']['gallery_items'][$i]['link_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link de DESTINO (ao clicar no popup)'),
        '#default_value' => $gallery_items[$i]['link_url'] ?? '',
        '#maxlength' => 512,
      ];

      $form['image_settings']['gallery_items'][$i]['link_target'] = [
        '#type' => 'radios',
        '#title' => $this->t('Abrir link de destino em'),
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
  
  /**
   * Manipulador de submissão customizado para o botão "Limpar este slot".
   *
   * Esta função limpa os dados de um slot específico da galeria de imagens,
   * tanto na memória do formulário quanto na configuração salva, e então
   * reconstrói o formulário para refletir a mudança.
   *
   * @param array $form
   * Uma array associativa contendo a estrutura do formulário.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * O estado atual do formulário.
   */
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('mikedelta_popup.settings');
    $gallery_values = $form_state->getValue('gallery_items');

    $saved_gallery_items = [];
    if (!empty($gallery_values)) {
      foreach ($gallery_values as $item) {
        $source_type = $item['image_source_type'];
        
        $item_to_save = [
          'image_source_type' => $source_type,
          'image_fid' => NULL,
          'image_external_url' => '',
          'link_url' => $item['link_url'],
          'link_target' => $item['link_target'],
        ];

        if ($source_type === 'upload' && !empty($item['upload_container']['image_fid'][0])) {
          $fid = $item['upload_container']['image_fid'][0];
          $file = File::load($fid);
          if ($file) {
            $file->setPermanent();
            $file->save();
          }
          $item_to_save['image_fid'] = $fid;
          $saved_gallery_items[] = $item_to_save;
        } elseif ($source_type === 'external' && !empty($item['external_container']['image_external_url'])) {
          $item_to_save['image_external_url'] = $item['external_container']['image_external_url'];
          $saved_gallery_items[] = $item_to_save;
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

    $cache_url = Url::fromRoute('system.performance_settings')->toString();
    $warning_message = $this->t('As alterações podem não ser visíveis imediatamente devido ao sistema de cache. <a href=":cache_url">Limpe todos os caches</a> para garantir que as novas configurações sejam aplicadas.', [
      ':cache_url' => $cache_url,
    ]);

    $this->messenger->addStatus($this->t('As configurações do MikeDelta Popup foram salvas com sucesso.'));
    $this->messenger->addWarning($warning_message);
  }
}