<?php

namespace TodoList\Components;

use Kdyby\Doctrine\QueryBuilder;

interface ITasksListControlFactory
{
    /**
     * @param QueryBuilder $qb
     * @return TasksListControl
     */
    public function create(QueryBuilder $qb);
}