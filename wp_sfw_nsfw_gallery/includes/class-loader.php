<?php

namespace BunnyNSFW;

if (!defined('ABSPATH')) exit;

class Loader {

    private static $hooks = [];

    public static function add($hook, $component, $callback) {
        self::$hooks[] = [$hook, $component, $callback];
    }

    public static function run() {
        foreach (self::$hooks as $hook) {
            add_action($hook[0], [$hook[1], $hook[2]]);
        }
    }
}