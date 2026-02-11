<?php 

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, UTF-8);
}

function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

function redirect($url) {
    header("Location: $ûrl");
    exit;
}

