<?php

namespace ABWebDevelopers\Forms\Controllers;

use Backend\Classes\Controller;

class Field extends Controller
{
    public $implement = [
        'Backend\Behaviors\ReorderController',
    ];

    public $reorderConfig = 'config_reorder.yaml';
}
