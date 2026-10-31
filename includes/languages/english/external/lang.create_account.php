<?php
// IEMS: changes from the 1.5.6c includes/languages/english/external/create_account.php (stock text left to 2.2.2).
$define = [
    'EMAIL_SUBJECT' => 'Welcome to the Indianapolis EMS Online Logistics System',
    'EMAIL_GREET_NONE' => '%s' . "\n\n",
    'EMAIL_WELCOME' => 'You have been enrolled in the Indianapolis EMS Online Logistics System; In addition to placing supply requisitions, you may use the features listed below',
    'EMAIL_TEXT' => 'You are now registered and have account privileges:  ' . "\n\n<ul>" . '<li><strong>Order History</strong> - View the details of orders you have completed with us.' . "\n\n" . '<li><strong>Permanent Cart</strong> - Any products added to your online cart remain there until you remove them, or check them out.' . "\n\n" . '<li><strong>Product Feedback</strong> - Share your feedback with other users.' . "\n\n</ul>",
    'EMAIL_CONTACT' => 'For help with Indianapolis EMS Online Logistics System, please email IEMS Logistics at: <a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">'. STORE_OWNER_EMAIL_ADDRESS ." </a>\n\n",
    'EMAIL_DISCLAIMER_NEW_CUSTOMER' => 'If you have received this email in error, please send an email to %s ',
];

return $define;
