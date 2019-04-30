<?php

namespace Mshule\LaravelPipes;

use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnexpectedValueException;

class PipeAction
{
    /**
     * Parse the given action into an array.
     *
     * @param string $cue
     * @param mixed  $action
     *
     * @return array
     */
    public static function parse($cue, $action)
    {
        // If no action is passed in right away, we assume the user will make use of
        // fluent piping. In that case, we set a default closure, to be executed
        // if the user never explicitly sets an action to handle the given cue.
        if (is_null($action)) {
            return static::missingAction($cue);
        }

        // If the action is already a Closure instance, we will just set that instance
        // as the "uses" property, because there is nothing else we need to do when
        // it is available. Otherwise we will need to find it in the action list.
        if (is_callable($action)) {
            return ! is_array($action) ? ['uses' => $action] : [
                'uses' => $action[0] . '@' . $action[1],
                'controller' => $action[0] . '@' . $action[1],
            ];
        }

        // If no "uses" property has been set, we will dig through the array to find a
        // Closure instance within this list. We will set the first Closure we come
        // across into the "uses" property that will get fired off by this route.
        elseif (! isset($action['uses'])) {
            $action['uses'] = static::findCallable($action);
        }

        if (is_string($action['uses']) && ! Str::contains($action['uses'], '@')) {
            $action['uses'] = static::makeInvokable($action['uses']);
        }

        return $action;
    }

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
     * Find the callable in an action array.
     *
     * @param array $action
     *
     * @return callable
     */
    protected static function findCallable(array $action)
    {
        return Arr::first($action, function ($value, $key) {
            return is_callable($value) && is_numeric($key);
        });
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
