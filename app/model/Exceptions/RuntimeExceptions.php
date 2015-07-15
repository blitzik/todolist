<?php

namespace TodoList\RuntimeExceptions;

class RuntimeException extends \RuntimeException {}

    class AuthenticationException extends RuntimeException {}

    class UsernameDuplicityException extends RuntimeException {}

    class EmailDuplicityException extends RuntimeException {}

    class ProjectNotFoundException extends RuntimeException {}

    class TaskNotFoundException extends RuntimeException {}