<?php
/**
 * @file
 * Contains \Drupal\staff_profile\Plugin\Search\StaffProfileSearch
 */
namespace Drupal\staff_profile\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Render\RendererInterface;
use Drupal\staff_profile\StaffProfileInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Handles searching from staff_profile_profile entities
 *
 * @SearchPlugin(
 *  id = "staff_profile_profile_search",
 *  title = @Translation("Staff Profile Search")
 * )
 */
class StaffProfileSearch extends ConfigurableSearchPluginBase implements AccessibleInterface, SearchIndexingInterface {
  /**
   * A database connection object.
   * @var \Drupal\Core\Database\Connection
   */
   protected $database;

  /**
   * An entity manager object.
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
   protected $entityManager;

  /**
   * A module manager object.
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
   protected $moduleHandler;

  /**
   * A config object for 'search.settings'.
   * @var \Drupal\Core\Config\Config
   */
   protected $searchSettings;

  /**
   * The language manager.
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
   protected $languageManager;

  /**
   * The Drupal account to use for checking for access to advanced search.
   * @var \Drupal\Core\Session\AccountInterface
   */
   protected $account;

  /**
   * The Renderer service to format the entity.
   * @var \Drupal\Core\Render\RendererInterface
   */
   protected $renderer;

  /**
   * An array of additional rankings from hook_ranking().
   * @var array
   */
  protected $rankings;

  /**
   * Array of advanced search options
   * @var array
   */
   protected $advanced = array(
     'field_last_name' => array(
       'column' => 's.field_last_name',
     ),

     'field_first_name' => array(
       'column' => 's.field_first_name',
     ),

     'field_base_county' => array(
       'column' => 's.field_base_county',
     ),

     'field_counties_served' => array(
       'column' => 'sfcs.field_counties_served_target_id',
     ),

     'field_program_area_s_' => array(
       'column' => 's.field_program_area_s_',
     ),

   );

   /**
    * A constant for setting and checking the query string.
    */
    const ADVANCED_FORM = 'advanced-form';

    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
    }

    /**
     * Constructs \Drupal\staff_profile\Plugin\Search\StaffProfileSearch.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, Config $search_settings, LanguageManagerInterface $language_manager, RendererInterface $renderer, AccountInterface $account = NULL) {
      $this->database = $database;
      $this->entityManager = $entity_manager;
      $this->moduleHandler = $module_handler;
      $this->searchSettings = $search_settings;
      $this->languageManager = $language_manager;
      $this->renderer = $renderer;
      $this->account = $account;
      parent::__construct($configuration, $plugin_id, $plugin_definition);

      $this->addCacheTags(['staff_profile_list']);
    }

    /**
     * {@inheritdoc}
     */
    public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
      $result = AccessResult::allowedIfHasPermission($account, 'access content');
      return $return_as_object ? $result : $result->isAllowed();
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchExecutable() {
      return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
      return $this->getPluginId();
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
      if ($this->isSearchExecutable()) {
        $results = $this->findResults();
        if ($results) {
          return $this->prepareResults($results);
        }
      }
      return array();
    }

    /**
     * Queries results and sets status messages
     */
    protected function findResults() {
      $keys = $this->keywords;
      $query = $this->database
        ->select('search_index', 'i', array('target' => 'replica'))
        ->extend('Drupal\search\SearchQuery')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

      $query->join('staff_profile_entity', 's', 's.id = i.sid');
      $query->condition('s.status', 1);
      if ($keys) {
        $query->searchExpression($keys, $this->getPluginId());
      }
      //Wildcards in LIKE condition https://drupal.stackexchange.com/questions/281575/core-search-names-with-wildcards

      $parameters = $this->getParameters();
      if (!empty($parameters['f']) && is_array($parameters['f'])) {
        $filters = array();
        $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
        foreach ($parameters['f'] as $item) {
          if (preg_match($pattern, $item, $m)) {
            if ($m[1] == 'field_counties_served') {
              $m[2] = explode(',',$m[2]);
              foreach($m[2] as $val) {
                $filters[$m[1]][$val] = $val;
              }
            } else {
              $filters[$m[1]][$m[2]] = $m[2];
            }
          }
        }

        foreach ($filters as $option => $matched) {
          if(is_array($matched)) {
            $matched = array_keys($matched);
          }
          $info = $this->advanced[$option];
          $operator = empty($info['operator']) ? 'OR' : $info['operator'];
          $where = new Condition($operator);
          foreach ($matched as $value) {
            $where->condition($info['column'], $value);
          }
          $query->condition($where);
          debug($query);
          $query->leftJoin('staff_profile_profile__field_counties_served', 'sfcs', 's.id = sfcs.entity_id');
          if (!empty($info['join'])) {
            $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
          }
        }
      }
      if ($keys == "") {
        //Add advanced keywords to search
        $backupkeys = "";
        foreach ($filters as $name => $keyword) {
          if ($name != "field_base_county" && $name != "field_counties_served") {
            if (is_array($keyword)) {
              foreach ($keyword as $key => $word) {
                $backupkeys .= (strlen($backupkeys) > 0 ? " OR " : "") . $word;
              }
            } else {
              $backupkeys .= (strlen($backupkeys) > 0 ? " OR " : "") . $keyword;
            }
          }
        }
        //Only add counties if we do not have enought characters
        if (strlen($backupkeys) <= 3) {
          $names = ['field_base_county', 'field_counties_served'];
          foreach ($names as $name) {
            if (strlen($backupkeys) <= 3) {
              $keyword = $filters[$name];
              if (is_array($keyword)) {
                foreach ($keyword as $key => $tid) {
                  $backupkeys .= (strlen($backupkeys) > 0 ? " OR " : "") . '"' . Term::load($tid)->getName(). '"';
                }
              } else {
                $backupkeys .= (strlen($backupkeys) > 0 ? " OR " : "") . '"' . Term::load($keyword)->getName() . '"';
              }
            }
          }
        }
        $query->searchExpression($backupkeys, $this->getPluginId());
      }

      $this->addStaffProfileRankings($query);

      //TODO add partial match support
      $find = $query
        ->fields('i', array('langcode'))
        ->groupBy('i.langcode')
        ->limit(25)
        ->execute();
      $status = $query->getStatus();
      //If we find nothing add wildcards
      if (!$find && $keys && preg_match("/\%/", $keys) == FALSE) {
        $keys = explode(" ", $keys);
        foreach ($keys as $name => $key) {
          $keys[$name] = "%" . $key . "%";
          debug($keys[$name]);
        }
        $keys = implode(" ", $keys);
        debug($keys);
        $query->searchExpression($keys, $this->getPluginId());
        $find = $query->execute();
        $status = $query->getStatus();
      }

      debug($query);
      debug($find);

      if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
        drupal_set_message($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in the search.', array('@count' => $this->searchSettings->get('and_or_limit'))), 'warning');
      }

      if ($status & SearchQuery::LOWER_CASE_OR) {
        drupal_set_message($this->t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>llamas OR alpacas</strong>.'), 'warning');
      }

      if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
        drupal_set_message($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one key word to match in a profile, and punctuation is ignored', 'You must include at least one keyword to match in a profile. Keywords must be at least @count characters and punctuation is ignored.'), 'warning');
      }
      return $find;
    }

    /**
     * Prepares search results for rendering
     */
    protected function prepareResults($found) {
      $results = array();
      $entity_storage = $this->entityManager->getStorage('staff_profile_profile');
      $entity_render = $this->entityManager->getViewBuilder('staff_profile_profile');
      $keys = $this->keywords;

      foreach ($found as $item) {
        $entity = $entity_storage->load($item->sid)->getTranslation($item->langcode);
        $build = $entity_render->view($entity, 'search_result', $item->langcode);
        unset($build['#theme']);

        //Invoke removeFromSnippet
        //$build['#pre_render'][] = array($this, 'removeFromSnippet');


        // Adding image requires changes to item-list--search-results.html.twig in the theme
        // template_preprocess_search_result()
        // $profile_image = \Drupal\file\Entity\File::load($entity->field_profile_image->getValue()[0]['target_id']);
        // $img_render = array();
        // if ($profile_image != NULL) {
        //   $img_vars = array(
        //     'style_name' => 'thumbnail',
        //     'uri' => $profile_image->getFileUri(),
        //   );
        //   $image = \Drupal::service('image.factory')->get($profile_image->getFileUri());
        //   if($image->isValid()) {
        //     $img_vars['width'] = $image->getWidth();
        //     $img_vars['height'] = $image->getHeight();
        //   } else {
        //     $img_vars['width'] = $img_vars['height'] = NULL;
        //   }
        //   $img_render = [
        //     '#theme' => 'image_style',
        //     '#width' =>$img_vars['width'],
        //     '#height' => $img_vars['height'],
        //     '#style_name' => $img_vars['style_name'],
        //     '#uri' => $img_vars['uri'],
        //   ];
        //   $this->addCacheableDependency($img_render, $profile_image);
        // }
        // $build['image'] = $img_render;

        $rendered = $this->renderer->renderPlain($build);

        $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
        $rendered .= ' ' . $this->moduleHandler->invokeAll('staff_profile_update_index', [$entity]);

        $extra = $this->moduleHandler->invokeAll('staff_profile_search_result', [$entity]);

        $language = $this->languageManager->getLanguage($item->langcode);
        //Remove owner account text and "Array" if it is present
        $rendered = preg_replace('#<div><a href="(.*?)</a></div>.+?Array?#is', '', $rendered);

        $result = array(
          'link' => $entity->url(
            'canonical',
            array(
              'absolute' => TRUE,
              'language' => $language,
            )
          ),
          'type' => 'Staff Profile',
          'title' => $entity->label(), //$entity->field_first_name->value . ' ' . $entity->field_last_name->value
          'staff_profile_profile' => $entity,
          'extra' => $extra,
          'score' => $item->calculated_score,
          'snippet' => search_excerpt($keys, $rendered, $item->langcode),
          'langcode' => $entity->language()->getId(),
        );

        // if ($img_vars) {
        //   $result['image'] = $img_render;
        // }
        $this->addCacheableDependency($entity);
        $this->addCacheableDependency($entity->getOwner());

        $results[] = $result;
      }
      return $results;
    }

    /**
     * Remove result data
     */
    public function removeFromSnippet($build) {
      unset($build['body']);
      unset($build['user_id']);
    }

    /**
     * Add configured rankings to search query
     */
    protected function addStaffProfileRankings($query) {
      if ($ranking = $this->getRankings()) {
        $tables = &$query->getTables();

        foreach ($ranking as $rank => $values) {

          if (isset($this->configuration['rankings'][$rank]) && !empty($this->configuration['rankings'][$rank])) {
            $entity_rank = $this->configuration['rankings'][$rank];

            if (isset($values['join']) && !isset($tables[$values['join']['alias']])) {
              $query->addJoin($values['join']['type'], $values['join']['table'], $values['join']['alias'], $values['join']['on']);
            }

            $arguments = isset($values['arguments']) ? $values['arguments'] : array();

            $query->addScore($values['score'], $arguments, $entity_rank);
          }
        }
      }
    }

    /**
     * {@inheritdoc}
     */
    public function updateIndex() {
      $limit = (int) $this->searchSettings->get('index.cron_limit');

      $result = $this->database->queryRange("SELECT s.id, MAX(sd.reindex) FROM {staff_profile_entity} s LEFT JOIN {search_dataset} sd ON sd.sid = s.id AND sd.type = :type WHERE (sd.sid IS NULL OR sd.reindex <> 0) AND s.status = 1 GROUP BY s.id ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, s.id ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'replica'));

      $rids = $result->fetchCol();
      \Drupal::logger('staff_profile')->info('Indexing ' . count($rids) . ' Staff Profiles');
      if (!$rids) {
        return;
      }

      $entity_storage = $this->entityManager->getStorage('staff_profile_profile');

      foreach ($entity_storage->loadMultiple($rids) as $entity) {
        $this->indexStaffProfile($entity);
      }
    }

    /**
     * Index a single staff profile
     */
    protected function indexStaffProfile(StaffProfileInterface $entity) {
      $languages = $entity->getTranslationLanguages();

      $entity_render = $this->entityManager->getViewBuilder('staff_profile_profile');

      foreach ($languages as $language) {
        $entity = $entity->getTranslation($language->getId());
        $build = $entity_render->view($entity, 'search_index', $language->getId());
        unset($build['#theme']);



        $build['search_title'] = [
          '#prefix' => '<h1>',
          '#plain-text' => $entity->field_first_name->value . ' ' . $entity->field_last_name->value,
          '#suffix' => '</h1>',
          '#weight' => -1000,
        ];

        $text = $this->renderer->renderPlain($build);
        //Remove labels
        $text = preg_replace('#<div class="field__label">(.*?)</div>#', '', $text);
        $extra = $this->moduleHandler->invokeAll('staff_profile_update_index', [$entity]);

        foreach ($extra as $t) {
          $text .= $t;
        }
        //Add name
        $text .= ' ' . $entity->field_first_name->value . ' ' . $entity->field_last_name->value;

        search_index($this->getPluginId(), $entity->id(), $language->getId(), $text);
      }
    }

    /**
     * {@inheritdoc}
     */
    public function indexClear() {
      search_index_clear($this->getPluginId());
    }

    /**
     * {@inheritdoc}
     */
    public function markForReindex() {
      search_mark_for_reindex($this->getPluginId());
    }

    /**
     * {@inheritdoc}
     */
    public function indexStatus() {
      $total = $this->database->query('SELECT COUNT(*) FROM {staff_profile_entity}')->fetchField();
      $remaining = $this->database->query("SELECT COUNT(DISTINCT s.id) FROM {staff_profile_entity} s LEFT JOIN {search_dataset} sd ON sd.sid = s.id AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();
      return array('remaining' => $remaining, 'total' => $total);
    }


    /**
     * {@inheritdoc}
     */
    public function searchFormAlter(array &$form, FormStateInterface $form_state) {
      $parameters = $this->getParameters();
      $keys = $this->getKeywords();
      $used_advanced = !empty($parameters[self::ADVANCED_FORM]);

      if ($used_advanced) {

        $f = isset($parameters['f']) ? (array) $parameters['f'] : array();
        $defaults = $this->parseAdvancedDefaults($f, $keys);

      } else {

        $defaults = array('keys' => $keys);

      }

      $form['basic']['keys']['#default_value'] = $defaults['keys'];

      // $form['advanced'] = array(
      //   '#type' => 'details',
      //   '#title' => t('Advanced search'),
      //   '#attributes' => array('class' => array('search-advanced')),
      //   '#access' => $this->account && $this->account->hasPermission('use advanced search'),
      //   '#open' => $used_advanced,
      // );

      $form['advanced']['misc-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('By Staff Information'),
      );

      $form['advanced']['misc-fieldset']['field_last_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Last Name'),
        '#description' => t('Search last names for exact matches.'),
        '#default_value' => isset($defaults['field_last_name']) ? $defaults['field_last_name'] : array(),
      );

      $form['advanced']['misc-fieldset']['field_first_name'] = array(
        '#type' => 'textfield',
        '#title' => t('First Name'),
        '#description' => t('Search first names for exact matches.'),
        '#default_value' => isset($defaults['field_first_name']) ? $defaults['field_first_name'] : array(),
      );

      $tag_terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('counties-in-iowa');
      $tags = array();
      $tags[''] = "- None -";
      foreach ($tag_terms as $tag_term) {
        $tags[$tag_term->tid] = $tag_term->name;
      }

      $form['advanced']['misc-fieldset']['field_base_county'] = array(
        '#type' => 'select',
        '#options' => $tags,
        '#title' => t('Base County'),
        '#default_value' => '',
        '#description' => t('Search by base county office.'),
        '#default_value' => isset($defaults['field_base_county']) ? $defaults['field_base_county'] : array(),
      );
      unset($tags['']);

      $form['advanced']['misc-fieldset']['field_counties_served'] = array(
        '#type' => 'select',
        '#options' => $tags,
        '#title' => t('Counties Served'),
        '#default_value' => '',
        '#description' => t('Search by counties served. Control or Command click to select multiple.'),
        '#multiple' => TRUE,
        '#default_value' => isset($defaults['field_counties_served']) ? $defaults['field_counties_served'] : array(),
      );

      $form['advanced']['misc-fieldset']['field_program_area_s_'] = array(
        '#type' => 'select',
        '#title' => t('Program Areas'),
        '#description' => t('Search program areas for exact matches.'),
        '#default_value' => isset($defaults['field_program_area_s_']) ? $defaults['field_program_area_s_'] : array(),
        '#options' => array(
          "" => "- None -",
          "4-H Youth" => "4-H Youth",
          "Administration" => "Administration",
          "Agriculture" => "Agriculture",
          "Business & Industry" => "Business & Industry",
          "Communications & External Relations" => "Communications & External Relations",
          "Communities" => "Communities",
          "Continuing Education & Professional Development" => "Continuing Education & Professional Development",
          "Human Sciences" => "Human Sciences",
        ),
      );
      $form['advanced']['misc-fieldset']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Search'),
        '#prefix' => '<div class="action">',
        '#suffix' => '</div>',
        '#weight' => 100,
      );

      // $form['advanced']['keywords-fieldset'] = array(
      //   '#type' => 'fieldset',
      //   '#title' => t('Keywords'),
      // );
      //
      // $form['advanced']['keywords-fieldset']['keywords']['or'] = array(
      //   '#type' => 'textfield',
      //   '#title' => t('Containing any of the words'),
      //   '#size' => 30,
      //   '#maxlength' => 255,
      //   '#default_value' => isset($defaults['or']) ? $defaults['or'] : '',
      // );
      //
      // $form['advanced']['keywords-fieldset']['keywords']['phrase'] = array(
      //   '#type' => 'textfield',
      //   '#title' => t('Containing the phrase'),
      //   '#size' => 30,
      //   '#maxlength' => 255,
      //   '#default_value' => isset($defaults['phrase']) ? $defaults['phrase'] : '',
      // );
      //
      // $form['advanced']['keywords-fieldset']['keywords']['negative'] = array(
      //   '#type' => 'textfield',
      //   '#title' => t('Containing none of the words'),
      //   '#size' => 30,
      //   '#maxlength' => 255,
      //   '#default_value' => isset($defaults['negative']) ? $defaults['negative'] : '',
      // );
      //
      // $form['advanced']['keywords-fieldset']['submit'] = array(
      //   '#type' => 'submit',
      //   '#value' => t('Search'),
      //   '#prefix' => '<div class="action">',
      //   '#suffix' => '</div>',
      //   '#weight' => 100,
      // );
    }

    /**
     * {@inheritdoc}
     */
    public function buildSearchUrlQuery(FormStateInterface $form_state) {
      $keys = trim($form_state->getValue('keys'));
      $advanced = FALSE;

      $filters = array();

      if ($form_state->hasValue('field_first_name') && !empty($value = trim($form_state->getValue('field_first_name')))) {
        $filters[] = 'field_first_name:' . $value;
        $advanced = TRUE;
      }

      if ($form_state->hasValue('field_last_name') && !empty($value = trim($form_state->getValue('field_last_name')))) {
        $filters[] = 'field_last_name:' . $value;
        $advanced = TRUE;
      }

      if ($form_state->hasValue('field_base_county') && !empty($value = trim($form_state->getValue('field_base_county')))) {
        $filters[] = 'field_base_county:' . $value;
        $advanced = TRUE;
      }

      if ($form_state->hasValue('field_counties_served') && !empty($value = trim(implode(',',$form_state->getValue('field_counties_served'))))) {
        $filters[] = 'field_counties_served:' . $value;
        $advanced = TRUE;
      }

      if ($form_state->hasValue('field_program_area_s_') && !empty($value = trim($form_state->getValue('field_program_area_s_')))) {
        $filters[] = 'field_program_area_s_:' . $value;
        $advanced = TRUE;
      }

      if ($form_state->getValue('or') != '') {
        if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('or'), $matches)) {
          $keys .= ' ' . implode(' OR ', $matches[1]);
          $advanced = TRUE;
        }
      }

      if ($form_state->getValue('negative') != '') {
        if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('negative'), $matches)) {
          $keys .= ' -' . implode(' -', $matches[1]);
          $advanced = TRUE;
        }
      }

      if ($form_state->getValue('phrase') != '') {
        $keys .= ' "' . str_replace('"', ' ', $form_state->getValue('phrase')) . '"';
        $advanced = TRUE;
      }

      $keys = trim($keys);
      $query = array('keys' => $keys);

      if ($filters) {
        $query['f'] = $filters;
      }

      if ($advanced) {
        $query[self::ADVANCED_FORM] = '1';
      }

      return $query;
    }

    /**
     * Parses the advanced search form default values
     */
    protected function parseAdvancedDefaults($f, $keys) {
      $defaults = array();

      foreach ($f as $advanced) {
        list($key, $value) = explode(':', $advanced, 2);
        if (!isset($defaults[$key])) {
          if($key == 'field_counties_served') {
            $defaults[$key] = explode(',',$value);
          } else {
            $defaults[$key] = $value;
          }
        } else {
          $defaults[$key] = array();
        }
      }

      //Split keywords
      $matches = array();
      $keys = ' ' . $keys . ' ';
      if (preg_match('/ "([^"]+)" /', $keys, $matches)) {
        $keys = str_replace($matches[0], ' ', $keys);
        $defaults['phrase'] = $matches[1];
      }

      //Pull out negatives
      if (preg_match_all('/ -([^ ]+)/', $keys, $matches)) {
        $keys = str_replace($matches[0], ' ', $keys);
        $defaults['negative'] = implode(' ', $matches[1]);
      }

      //Pull up to one set if OR values
      if (preg_match('/ [^ ]+( OR [^ ]+)+ /', $keys, $matches)) {
        $keys = str_replace($matches[0], ' ', $keys);
        $words = explode(' OR ', trim($matches[0]));
        $defaults['or'] = implode(' ', $words);
      }

      //Remaining back into keywords
      $defaults['keys'] = trim($keys);
      return $defaults;
    }

    /**
     * Get ranking definitions from hook_ranking()
     */
    protected function getRankings() {
      if (!$this->rankings) {
        $this->rankings = $this->moduleHandler
          ->invokeAll('ranking');
      }
      return $this->rankings;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
      $configuration = array(
        'rankings' => array(),
      );
      return $configuration;
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
      $form['content_ranking'] = array(
        '#type' => 'details',
        '#title' => t('Content ranking'),
        '#open' => TRUE,
      );

      $form['content_ranking']['info'] = array(
        '#markup' => '<p><em>' . $this->t('Influence is a numeric multiplier used in ordering search results. A higher number means the corresponding factor has more influence on search results; zero means the factor is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em></p>',
      );

      // Prepare table.
      $header = [$this->t('Factor'), $this->t('Influence')];
      $form['content_ranking']['rankings'] = array(
        '#type' => 'table',
        '#header' => $header,
      );

      // Note: reversed to reflect that higher number = higher ranking.
      $range = range(0, 10);
      $options = array_combine($range, $range);
      foreach ($this->getRankings() as $var => $values) {
        $form['content_ranking']['rankings'][$var]['name'] = array(
          '#markup' => $values['title'],
        );

        $form['content_ranking']['rankings'][$var]['value'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#attributes' => ['aria-label' => $this->t("Influence of '@title'", ['@title' => $values['title']])],
          '#default_value' => isset($this->configuration['rankings'][$var]) ? $this->configuration['rankings'][$var] : 0,
        );
      }
      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
      foreach ($this->getRankings() as $var => $values) {

        if (!$form_state->isValueEmpty(['rankings', $var, 'value'])) {
          $this->configuration['rankings'][$var] = $form_state->getValue(['rankings', $var, 'value']);
        } else {
          unset($this->configuration['rankings'][$var]);
        }

      }
    }

}
