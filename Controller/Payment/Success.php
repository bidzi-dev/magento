<?php

/**
 * @category    Payments
 * @package     Bidzi_Cards
 * @author      Federico Balderas
 * @copyright   Bidzi (http://bidzi.mx)
 * @license     http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0
 */

namespace Bidzi\Cards\Controller\Payment;

use Bidzi\Cards\Model\Payment as BidziPayment;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Framework\App\RequestInterface;


class Success extends Action
{
    protected $checkoutSession;
    protected $cartManagement;
    protected $order;
    protected $bidziPayment;
    protected $logger;
    protected $cartRepository;
    protected $request;

    /**
     *
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param checkoutSession $checkoutSession
     * @param Order $order
     * @param RequestInterface $request
     * @param BidziPayment $bidziPayment
     * @param \Psr\Log\LoggerInterface $logger_interface
     *
     */
    public function __construct(
        Context $context,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        CheckoutSession $checkoutSession,
        Order $order,
        RequestInterface $request,
        BidziPayment $payment,
        \Psr\Log\LoggerInterface $logger_interface,
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->order = $order;
        $this->request = $request;
        $this->bidziPayment = $payment;
        $this->logger = $logger_interface;

        parent::__construct($context);
    }
    /**
     * Load the page defined in view/frontend/layout/bidzi_index_webhook.xml
     * URL /bidzi/payment/success
     *
     * @url https://magento.stackexchange.com/questions/197310/magento-2-redirect-to-final-checkout-page-checkout-success-failed?rq=1
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            // Obtener el valor del parámetro 'payment_id'
            $bidzi_payment_id = $this->request->getParam('payment_id');
            $this->logger->debug('#SUCCESS', array('bidzi_payment_id' => $bidzi_payment_id));

            // Obtener la cotización del carrito actual
            $quote = $this->checkoutSession->getQuote();

            // Verificar si la cotización es válida y tiene productos
            if (!$quote->getId()) {
                throw new LocalizedException(__('The cart has no products.'));
            }

            // Obtener el pago de Bidzi
            $bidzi_payment = $this->bidziPayment->getBidziPayment($bidzi_payment_id);
            $this->logger->debug('#SUCCESS', array('bidzi_payment' => $bidzi_payment));

            if (!$bidzi_payment) {
                throw new LocalizedException(__('Bidzi payment not found.'));
            }

            if ($bidzi_payment['status'] != 'COMPLETED') {
                throw new LocalizedException(__('Payment was not completed.'));
            }

            // Obtener el método de pago de Bidzi
            $payment_method_id = $bidzi_payment['payment_source']['payment_method_id'];
            $bidzi_payment_method = $this->bidziPayment->getBidziPaymentMethod($payment_method_id);
            $this->logger->debug('#SUCCESS', array('bidzi_payment_method' => $bidzi_payment_method));

            // Definir el método de pago
            $paymentData = [
                'method' => $this->bidziPayment->getCode(),
                'bidzi_token' => $payment_method_id,
                'device_session_id' => $bidzi_payment['device_session_id'],
                'payment_id' => $bidzi_payment['payment_id'],
                'cc_number' => $bidzi_payment_method['card']['last_four'],
                'cc_exp_month' => $bidzi_payment_method['card']['expiration_month'],
                'cc_exp_year' => $bidzi_payment_method['card']['expiration_year'],
                'cc_type' => $this->bidziPayment->getCCBrandCode($bidzi_payment_method['card']['brand'])
            ];

            $this->logger->debug('#SUCCESS', array('paymentData' => $paymentData));

            $quote->getPayment()->importData($paymentData);

            // Recalcular totales
            $quote->collectTotals();

            // Guardar la cotización antes de realizar la orden
            $this->cartRepository->save($quote);

            // Convertir el carrito en una orden
            $orderId = $this->cartManagement->placeOrder($quote->getId());

            // Cargar la orden creada
            $order = $this->order->load($orderId);

            // Mensaje de éxito
            $this->messageManager->addSuccessMessage(__('Order created successfully. Order ID: %1', $order->getIncrementId()));

            $this->logger->debug('#SUCCESS', array('redirect' => 'checkout/onepage/success'));

            // Redireccionar a una página de éxito o donde sea necesario
            return $this->_redirect('checkout/onepage/success');
        } catch (LocalizedException $e) {
            $this->logger->error('#SUCCESS', array('message' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()));
            // Mensaje de error
            $this->messageManager->addErrorMessage(__('Error creating order: %1', $e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error('#SUCCESS', array('message' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()));
            // Manejo de error genérico
            $this->messageManager->addErrorMessage(__('An error has occurred: %1', $e->getMessage()));
        }

        // Redireccionar a la página de carrito si hay un error
        return $this->_redirect('checkout/cart');
    }
}
