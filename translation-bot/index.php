<?php

if (file_exists('../config.php')) {
    include_once '../config.php';
} else {
    die('Please, created config file.');
}

die('Silence is golden.');
