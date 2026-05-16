<?php

if (!defined('ABSPATH')) exit;

function bunny_nsfw_get_cookie($name) {
    return $_COOKIE[$name] ?? null;
}

function bunny_nsfw_is_unlocked() {
    return isset($_COOKIE['bunny_nsfw_age']) && $_COOKIE['bunny_nsfw_age'] === '1';
}