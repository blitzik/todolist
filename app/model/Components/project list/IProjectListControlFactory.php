<?php

namespace TodoList\Components;

interface IProjectListControlFactory
{
    /**
     * @return ProjectListControl
     */
    public function create();
}