<?php
function mailtrap($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'sandbox.smtp.mailtrap.io';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 2525;
    $phpmailer->Username = '3d2c5a8fc8e8e0';
    $phpmailer->Password = '2a62879f112234';
}
add_action('phpmailer_init', 'mailtrap');