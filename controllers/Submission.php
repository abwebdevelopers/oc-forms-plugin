<?php namespace ABWebDevelopers\Forms\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use ABWebDevelopers\Forms\Models\Submission as Sub;

class Submission extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
    ];
    
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('ABWebDevelopers.Forms', 'bradie-forms', 'bradie-forms-submissions');
    }

    public function details($id) {
        $sub = Sub::with(['form'])->findOrFail($id);

        return $this->makePartial('details', [
            'submission' => $sub
        ]);
    }
}
