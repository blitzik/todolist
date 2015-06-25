<?php

namespace TodoList\Components;

interface IProjectFormControlFactory
{
    /**
     * @return ProjectFormControl
     */
    public function create();
}