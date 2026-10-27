<?php

if (!defined('ABSPATH')) exit;

function bunny_nsfw_get_cookie($name) {
    return $_COOKIE[$name] ?? null;
}

function bunny_nsfw_is_unlocked() {
    if ( function_exists( 'bunny_nsfw_is_protection_enabled' ) && ! bunny_nsfw_is_protection_enabled() ) {
        return true;
    }
    $cookie_name = function_exists( 'bunny_nsfw_get_configured_cookie_name' )
        ? bunny_nsfw_get_configured_cookie_name()
        : 'bunny_nsfw_age';

    return isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === '1';
}