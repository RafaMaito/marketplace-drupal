<?php

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides the payment method delete form.
 */
class PaymentMethodDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->getEntity();
    $payment_gateway = $payment_method->getPaymentGateway();
    if ($payment_gateway) {
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      try {
        $payment_gateway_plugin->deletePaymentMethod($payment_method);
      }
      catch (PaymentGatewayException $e) {
        $this->messenger()->addError($e->getMessage());
        $form_state->setRedirectUrl($this->getRedirectUrl());
        return;
      }
    }
    else {
      // Without a payment gateway, the remote payment method cannot
      // be deleted. Delete the local payment method only.
      $payment_method->delete();
    }

    $this->messenger()->addMessage($this->getDeletionMessage());
    $this->logDeletionMessage();
    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

}
