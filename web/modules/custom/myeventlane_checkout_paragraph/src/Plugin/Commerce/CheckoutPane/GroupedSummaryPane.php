<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkout_paragraph\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a grouped order summary by Event and Ticket Type using Views.
 *
 * @CommerceCheckoutPane(
 *   id = "grouped_order_summary",
 *   label = @Translation("Grouped order summary"),
 *   default_step = "_sidebar",
 *   wrapper_element = "fieldset"
 * )
 */
final class GroupedSummaryPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form): array {
    $order = $this->order;

    // Load the 'ticket_order_summary' view and display 'embed_1'.
    $view = Views::getView('ticket_order_summary');
    if ($view && $view->access('embed_1')) {
      $view->setDisplay('embed_1');
      $view->setArguments([$order->id()]);
      $view->preExecute();
      $view->execute();
      $rendered = $view->render();

      if (!empty($rendered)) {
        $pane_form['summary'] = $rendered;
      }
      else {
        // Return empty array to hide the pane when view is empty.
        return [];
      }
    }
    return $pane_form;
  }

}
