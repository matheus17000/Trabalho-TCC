<?php

$BASE_URL = '';

if (
    isset($_SERVER['HTTP_HOST']) &&
    ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')
) {
    $BASE_URL = '/erp-tcc';
}