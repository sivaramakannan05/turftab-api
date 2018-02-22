<?php

// Logs configuration for email,mobile and push notification

$config = array(
    'email_logs' => array(
        'level' => 'INFO',
        'type' => 'file',
        'format' => "{message}",
        'file_path' => 'email_logs',
        'prefix' => 'logs_',
        'extension' => 'txt'
    ),
    'mobile_logs' => array(
        'level' => 'INFO',
        'type' => 'file',
        'format' => "{message}",
        'file_path' => 'mobile_logs',
        'prefix' => 'logs_',
        'extension' => 'txt'
    ),
    'push_notification_logs' => array(
        'level' => 'INFO',
        'type' => 'file',
        'format' => "{message}",
        'file_path' => 'push_notification_logs',
        'prefix' => 'logs_',
        'extension' => 'txt'
    ),
    // 'email_criticals' => array(
    //     'level' => 'CRITICAL',
    //     'type' => 'email',
    //     'format' => "{date} - {level}: {message}",
    //     'to' => 'sivaramakannan05@gmail.com',
    //     'from' => 'sivaramakannan05@gmail.com',
    //     'subject' => 'New critical logging message'
    // )
);