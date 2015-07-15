<?php

namespace TodoList\Components;

interface ITaskLabelControlFactory
{
    /**
     * @param array $task
     * @return TaskLabelControl
     */
    public function create(array $task);
}