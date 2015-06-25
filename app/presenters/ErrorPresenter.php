<?php

namespace TodoList\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

class ErrorPresenter extends Presenter
{
    /**
     * @param  Exception
     * @return void
     */
    public function renderDefault($exception)
    {
        if ($exception instanceof BadRequestException) {
            $code = $exception->getCode();
            // load template 403.latte or 404.latte or ... 4xx.latte
            $this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx');
            // log to access.log
            \Tracy\Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');

        } else {
            $this->setView('500'); // load template 500.latte
            \Tracy\Debugger::log($exception, \Tracy\Debugger::ERROR); // and log exception
        }

        if ($this->isAjax()) { // AJAX request? Note this error in payload.
            $this->payload->error = TRUE;
            $this->terminate();
        }
    }
}