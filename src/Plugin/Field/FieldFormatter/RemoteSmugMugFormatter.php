<?php
namespace Drupal\staff_profile\Plugin\Field\FieldFormatter;

use \Drupal\Core\Field\FieldItemListInterface;
use \Drupal\Core\Field\FieldItemInterface;
use \Drupal\Core\Field\FormatterBase;


/**
 * Plugin field formatter for remote_smugmug_image
 *
 * @FieldFormatter(
 *   id = "remote_smugmug_image",
 *   label = @Translation("SmugMug Image"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class RemoteSmugMugFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
   public function viewElements(FieldItemListInterface $items, $langcode) {
     $element = array();
     foreach ($items as $delta => $item) {
       $element[$delta] = [
         '#type' => 'markup',
         '#markup' => $this->getEmbedCode($item),
         '#allowed_tags' => ['img', 'a', 'div'],
       ];
     }
     return $element;
   }

   /**
    * {@inheritdoc}
    */
   protected function getEmbedCode($value) {
     $url = "";
     if (is_string($value)) {
       $url = $value;
     } elseif ($value instanceof FieldItemInterface) {
       $class = get_class($value);
       $property = $class::mainPropertyName();
       if ($property) {
         $url = $value->$property;
       }
     }
     //TODO get better alttext
     return "<img src='" . $url . "' alt='Staff Portrait'>";
   }
}
