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
use Drupal\content_entity_example\ContactInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching from staff_profile_profile entities
 *
 * @SearchPlugin(
 *  id = "staff_profile_profile_search",
 *  title = @Translation("Staff Profile Search")
 * )
 */
class StaffProfileSearch extends ConfigurableSearchPluginBase implements AccessibleInterface/*, SearchIndexingInterface*/ {
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
   * The Renderer service to format the username and entity.
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

      $query->searchExpression($keys, $this->getPluginId());

      $parameters = $this->getParameters();
      if (!empty($parameters['f']) && is_array($parameters['f'])) {
        $filters = array();
        $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
        foreach ($parameters['f'] as $item) {
          if (preg_match($pattern, $item, $m)) {
            $filters[$m[1]][$m[2]] = $m[2];
          }
        }

        foreach ($filters as $option => $matched) {
          $info = $this->advanced[$option];
          $operator = empty($info['operator']) ? 'OR' : $info['operator'];
          $where = new Condition($operator);
          foreach ($matched as $value) {
            $where->condition($info['column'], $value);
          }
          $query->condition($where);
          if (!empty($info['join'])) {
            $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
          }
        }
      }
      $this->addStaffProfileRankings($query);
      $find = $query
        ->fields('i', array('langcode'))
        ->groupBy('i.langcode')
        ->limit(10)
        ->execute();

      debug($find);
      $status = $query->getStatus();
      debug($status);

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
        //unset($build['#theme']);

        //Invoke removeFromSnippet
        //$build['#pre_render'][] = array($this, 'removeFromSnippet');

        $rendered = $this->renderer->renderPlain($build);

        $this->addCachableDependency(CacheableMetadata::createFromRenderArray($build));
        $rendered .= ' ' . $this->moduleHandler->invokeAll('staff_profile_update_index', [$entity]);

        $extra = $this->moduleHandler->invokeAll('staff_profile_search_result', [$entity]);

        $language = $this->languageManager->getLanguage($item->langcode);

        $result = array(
          'link' => $entity->url(
            'canonical',
            array(
              'absolute' => TRUE,
              'language' => $language,
            )
          ),
          'type' => 'Staff Profile',
          'title' => $entity->label(),
          'staff_profile_profile' => $entity,
          'extra' => $extra,
          'score' => $item->calculated_score,
          'snippet' => search_excerpt($keys, $rendered, $item_>langcode),
          'langcode' => $entity->language()->getId(),
        );

        $this->addCachableDependency($entity);
        $this->addCachableDependency($entity->getOwner());

        $results[] = $result;
      }
      return $results;
    }

    /**
     * Remove result data
     */
    public function removeFromSnippet($build) {
      //unset($build['created']);
      //unset($build['uid']);
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

      $result = $this->database-queryRange("SELECT s.id, MAX(sd.reindex) FROM {staff_profile_entity} s LEFT JOIN {search_dataset} sd ON sd.sid = s.id AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0 GROUP BY s.id ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, s.id ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'replica'));

      $rids = $result->fetchCol();
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
    protected function indexStaffProfile($entity) {
      $languages = $entity->getTranslationLanguages();

      $entity_render = $this->entityManager->getViewBuilder('staff_profile_profile');

      foreach ($languages as $language) {
        $entity = $entity->getTranslation($language->getId());
        $build = $entity_render->view($entity, 'search_index', $language->getId());
        //unset($build['#theme']);

        $build['search_title'] = [
          '#prefix' => '<h1>',
          '#plain-text' => $entity->label(),
          '#suffix' => '</h1>',
          '#weight' => -1000,
        ];
        $text = $this->renderer->renderPlain($build);

        $extra = $this->moduleHandler->invokeAll('staff_profile_update_index', [$entity]);

        foreach ($extra as $t) {
          $text .= $t;
        }

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
      $remaining = $this->database->query("SELECT // COUNT(DISTINCT s.id) FROM {staff_profile_entity} s LEFT JOIN {search_dataset} sd ON sd.sid = s.id AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();

      return array('remaining' => $remaining, '$total' => $total);
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

      $form['advanced'] = array(
        '#type' => 'details',
        '#title' => t('Advanced search'),
        '#attributes' => array('class' => array('search-advanced')),
        '#access' => $this->account && $this->account->hasPermission('use advanced search'),
        '#open' => $used_advanced,
      );
      $form['advanced']['keywords-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Keywords'),
      );

      $form['advanced']['keywords-fieldset']['keywords']['or'] = array(
        '#type' => 'textfield',
        '#title' => t('Containing any of the words'),
        '#size' => 30,
        '#maxlength' => 255,
        '#default_value' => isset($defaults['or']) ? $defaults['or'] : '',
      );

      $form['advanced']['keywords-fieldset']['keywords']['phrase'] = array(
        '#type' => 'textfield',
        '#title' => t('Containing the phrase'),
        '#size' => 30,
        '#maxlength' => 255,
        '#default_value' => isset($defaults['phrase']) ? $defaults['phrase'] : '',
      );

      $form['advanced']['keywords-fieldset']['keywords']['negative'] = array(
        '#type' => 'textfield',
        '#title' => t('Containing none of the words'),
        '#size' => 30,
        '#maxlength' => 255,
        '#default_value' => isset($defaults['negative']) ? $defaults['negative'] : '',
      );

      $form['advanced']['misc-fieldset'] = array(
        '#type' => 'fieldset',
      );

      $form['advanced']['misc-fieldset']['note'] = array(
        '#markup' => t('You must still enter keyword(s) above when using these fields.'),
        '#weight' => -10,
      );

      $form['advanced']['misc-fieldset']['field_first_name'] = array(
        '#type' => 'textfield',
        '#title' => t('First Name'),
        '#description' => t('Search first names for exact matches.'),
        '#default_value' => isset($defaults['field_first_name']) ? $defaults['field_first_name'] : array(),
      );

      $form['advanced']['misc-fieldset']['field_last_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Last Name'),
        '#description' => t('Search last names for exact matches.'),
        '#default_value' => isset($defaults['field_last_name']) ? $defaults['field_last_name'] : array(),
      );

      $form['advanced']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Advanced search'),
        '#prefix' => '<div class="action">',
        '#suffix' => '</div>',
        '#weight' => 100,
      );
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
          $defaults[$key] = array();
        }
        $defaults[$key][] = $value;
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


    public function staff_profile_ranking() {
      $ranking = array(
        'relevance' => array(
          'title' => t('Keyword Relevance'),
          'score' => 'i.relevance',
        ),
      );
    }
}
