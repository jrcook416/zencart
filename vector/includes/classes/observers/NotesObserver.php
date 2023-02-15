<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class NotesObserver extends base {
   
   // Set to false if you do not wish any of these features 
   const PRODUCT_NOTES = true;
   const ORDER_NOTES = true;
   const CUSTOMER_NOTES = true;

   // You must have ORDER_NOTES set to true to use these 
   const PACKINGSLIP_NOTES_TOP = false;
   const PACKINGSLIP_NOTES_BOTTOM = true;
   const INVOICE_NOTES_TOP = false;
   const INVOICE_NOTES_BOTTOM = true;

    public function __construct() {
       $notifiers = []; 

       if (NotesObserver::PRODUCT_NOTES) { 
            $product_notifiers = array( 
                // Issued by includes/functions/general.php
                'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT',
                                  
                // Issued by includes/modules/update_product.php
                'NOTIFY_MODULES_UPDATE_PRODUCT_END',
                
                // Issued by includes/modules/{product_type}/collect_info.php
                'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS',
            );
            $notifiers = array_merge($notifiers, $product_notifiers); 
       }

       if (NotesObserver::ORDER_NOTES) { 
          // Issued by orders.php 
          $order_notifiers = array( 
             'NOTIFY_ADMIN_ORDERS_DEFAULT_ACTION', 
             'NOTIFY_ADMIN_ORDERS_AFTER_STATUS_LISTING', 
          );
          if (NotesObserver::PACKINGSLIP_NOTES_TOP) { 
            $order_notifiers[] = 'NOTIFY_ADMIN_ORDERS_PACKINGSLIP_ADDITIONAL_DATA_TOP'; 
          }
          if (NotesObserver::PACKINGSLIP_NOTES_BOTTOM) { 
            $order_notifiers[] = 'NOTIFY_ADMIN_ORDERS_PACKINGSLIP_ADDITIONAL_DATA_BOTTOM'; 
          }
          if (NotesObserver::INVOICE_NOTES_TOP) { 
            $order_notifiers[] = 'NOTIFY_ADMIN_ORDERS_INVOICE_ADDITIONAL_DATA_TOP'; 
          }
          if (NotesObserver::INVOICE_NOTES_BOTTOM) { 
            $order_notifiers[] = 'NOTIFY_ADMIN_ORDERS_INVOICE_ADDITIONAL_DATA_BOTTOM'; 
          }
          $notifiers = array_merge($notifiers, $order_notifiers); 
       }

       if (NotesObserver::CUSTOMER_NOTES) { 
          // Issued by customers.php 
          $customer_notifiers = array( 
             'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT',
             'ADMIN_CUSTOMER_UPDATE', 
          );
          $notifiers = array_merge($notifiers, $customer_notifiers); 
       }


       if (!empty($notifiers)) { 
          $this->attach ($this, $notifiers); 
       }
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3) {
        global $db;
        
        $notes = new Notes();
        switch ($eventID) {
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_PRODUCT':
                $notes->removeNote($p2, "products");
                break;

            case 'NOTIFY_MODULES_UPDATE_PRODUCT_END':
				$notes->updateNote($p2, "products");
                break;
                
            case 'NOTIFY_ADMIN_PRODUCT_COLLECT_INFO_EXTRA_INPUTS':
                if (!empty($p1->products_id)) {
                    $extra_product_inputs = $notes->createInputField($p1->products_id, "products");
                } else {
                    $extra_product_inputs = $notes->createPlaceholder("products");
                }
                if (is_array($extra_product_inputs)) {
                    $p2[] = $extra_product_inputs;
                }
                break;

            case 'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT':
                if (!empty($p1->customers_id)) {
                    $extra_customer_inputs = $notes->createInputField($p1->customers_id, "customers");
                } else {
                    $extra_customer_inputs = $notes->createPlaceholder("customers");
                }
                if (is_array($extra_customer_inputs)) {
                    $p2[] = $extra_customer_inputs;
                }
                break;

            case 'ADMIN_CUSTOMER_UPDATE':
                $notes->updateNote($p1, "customers");
                break;
            
            case 'NOTIFY_ADMIN_ORDERS_DEFAULT_ACTION': 
               if ($p3 == 'update_note') { 
                   $notes->updateNote($p1, "orders");

                   $redirect = zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(['action', 'language']) . ($admin_language !== $_SESSION['languages_code'] ? '&language=' . $admin_language : ''), 'NONSSL');
                   if (isset($_POST['camefrom']) && $_POST['camefrom'] === 'orderEdit') {
                       $redirect .= '&action=edit';
                   }
        zen_redirect($redirect);
               }
               break; 

            case 'NOTIFY_ADMIN_ORDERS_AFTER_STATUS_LISTING':
               $extra_order_inputs = $notes->createInputForm($p1, "orders");
               if (!empty($extra_order_inputs)) {
                    $p2 .= $extra_order_inputs;
               }
               break;

            case 'NOTIFY_ADMIN_ORDERS_PACKINGSLIP_ADDITIONAL_DATA_TOP': 
            case 'NOTIFY_ADMIN_ORDERS_PACKINGSLIP_ADDITIONAL_DATA_BOTTOM': 
            case 'NOTIFY_ADMIN_ORDERS_INVOICE_ADDITIONAL_DATA_TOP': 
            case 'NOTIFY_ADMIN_ORDERS_INVOICE_ADDITIONAL_DATA_BOTTOM':
               $extra_order_inputs = $notes->createDisplayField($p1, "orders");
               if (!empty($extra_order_inputs)) {
                    $p2 .= $extra_order_inputs;
               }
               break;

            default:
                break;
        }
    }
}
