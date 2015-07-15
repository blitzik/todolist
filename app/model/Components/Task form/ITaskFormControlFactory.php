<?php

namespace TodoList\Components;

interface ITaskFormControlFactory
{
    /**
     * @return TaskFormControl
     */
    public function create();
}