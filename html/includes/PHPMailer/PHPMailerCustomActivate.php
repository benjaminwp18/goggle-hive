<?php
  $mail = new PHPMailer();
  $mail->IsSMTP(); // enable SMTP
  $mail->SMTPDebug = false; // debugging: 1 = errors and messages, 2 = messages only
  $mail->SMTPAuth = true; // authentication enabled
  $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
  $mail->Host = 'smtp.gmail.com';
  $mail->Port = 465; // or 587
  $mail->IsHTML(true);
  $mail->Username = 'address@example.com';
  $mail->Password = 'your password';
  $mail->SetFrom('address@example.com', 'Goggle Hive');