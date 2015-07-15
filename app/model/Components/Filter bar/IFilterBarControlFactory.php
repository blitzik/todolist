<?php

namespace TodoList\Components;

interface IFilterBarControlFactory
{
    /**
     * @return FilterBarControl
     */
    public function create();
}