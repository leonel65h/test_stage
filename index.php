<?php

require 'header.php';

use Genesis as g;
use paiement\ModulePaiement\Controller\MonetbilController;

(new Request('hello'));

switch (Request::get('path')) {
    case 'hello':
        g::render('hello');
        break;


    //    case 'view1':
    //        g::render( 'view1', $Ctrl->Action() );
    //        break;
    //
    //    ...
    //
    //    case 'view2':
    //        g::render( 'view2', [$Ctrl->Action(), $Ctrl2->Action(), ... , $Ctrln->Action()] );
    //        break;


    default :
        // inclusion du layout du site
        g::render('404');
        break;
}

