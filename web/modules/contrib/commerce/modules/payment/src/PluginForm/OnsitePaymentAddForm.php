<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;

class OnsitePaymentAddForm extends PaymentGatewayFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }
    // The payment amount should not exceed the remaining order balance.
    $balance = $order->getBalance();
    $amount = $balance->isPositive() ? $balance : $balance->multiply(0);

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Amount'),
      '#default_value' => $amount->toArray(),
      '#required' => TRUE,
    ];
    $form['transaction_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction type'),
      '#title_display' => 'invisible',
      '#options' => [
        'authorize' => $this->t('Authorize only'),
        'capture' => $this->t('Authorize and capture'),
      ],
      '#default_value' => 'capture',
      '#access' => $this->plugin instanceof SupportsAuthorizationsInterface,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $capture = ($values['transaction_type'] == 'capture');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $capture);
  }

}
