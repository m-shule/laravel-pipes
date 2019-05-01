<?php

namespace Mshule\LaravelPipes;

use LogicException;
use UnexpectedValueException;
use Illuminate\Routing\RouteAction;

class PipeAction extends RouteAction
{
    /**
     * Get an action for a pipe that has no action.
     *
     * @param string $cue
     *
     * @return array
     */
    protected static function missingAction($cue)
    {
        return ['uses' => function () use ($cue) {
            throw new LogicException("Pipe for [{$cue}] has no action.");
        }];
    }

    /**
     * Make an action for an invokable controller.
     *
     * @param string $action
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    protected static function makeInvokable($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException("Invalid pipe action: [{$action}].");
        }

        return $action . '@__invoke';
    }
}
