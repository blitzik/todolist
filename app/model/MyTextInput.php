<?php

class MyTextInput extends \Nette\Forms\Controls\TextInput
{
    public function getControl()
    {
        $el =  parent::getControl();
        dump($el->{'data-nette-rules'});

        return $el;
    }

}