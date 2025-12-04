<?php

use Noctalys\Framework\View\View;

/**
 * Home page controller
 */
class HomeController
{
    public function main()
    {
        $data = [
            'title' => 'Noctalys Demo App',
            'message' => 'Welwome to noctalys demo app!',
            'mainText' => 'Noctalys',
            'subText' => 'A modern and lightweight PHP framework',
        ];
        View::render('home', $data);
    }
}