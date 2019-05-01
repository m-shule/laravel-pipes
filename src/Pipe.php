<?php

namespace Mshule\LaravelPipes;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mshule\LaravelPipes\Matching\PathValidator;
use Mshule\LaravelPipes\Matching\ParameterValidator;

class Pipe extends Route
{
    /**
     * The cue pattern the pipe responds to.
     *
     * @var string
     */
    public $cue;

    /**
     * The inputs the pipe respond to.
     *
     * @var array
     */
    public $inputs;

    /**
     * The piper instance used by the pipe.
     *
     * @var \Mshule\LaravelPipes\Piper
     */
    protected $piper;

    /**
     * The validators used by the pipes.
     *
     * @var array
     */
    public static $validators;

    /**
     * Create a new Pipe instance.
     *
     * @param string         $cue
     * @param \Closure|array $action
     * @param array|string   $inputs
     */
    public function __construct($cue, $action, $inputs = [])
    {
        parent::__construct(['GET'], $cue, $action);

        $this->cue = $cue;
        $this->inputs = (array) $inputs;
        $this->prefix($inputs);
    }

    /**
     * Parse the route action into a standard array.
     *
     * @param callable|array|null $action
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    protected function parseAction($action)
    {
        return PipeAction::parse($this->cue, $action);
    }

    /**
     * Set the piper instance on the pipe.
     *
     * @param \Mshule\LaravelPipes\Piper $piper
     *
     * @return $this
     */
    public function setPiper(Piper $piper)
    {
        $this->piper = $piper;

        return $this;
    }

    /**
     * Get the cue associated with the pipe.
     *
     * @return string
     */
    public function cue()
    {
        return $this->cue;
    }

    /**
     * Get the inputs the pipe responds to.
     *
     * @return array
     */
    public function inputs()
    {
        return $this->inputs;
    }

    /**
     * Bind the pipe to a given request for execution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function bind(Request $request)
    {
        $this->compileRoute();

        $this->parameters = (new PipeParameterBinder($this))
                        ->parameters($request);

        $this->originalParameters = $this->parameters;

        return $this;
    }

    /**
     * Get the pipe validators for the instance.
     *
     * @return array
     */
    public static function getValidators()
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }

        // To match the pipe, we will use a chain of responsibility pattern with the
        // validator implementations. We will spin through each one making sure it
        // passes and then we will know if the pipe as a whole matches request.
        return static::$validators = [
            new PathValidator, new ParameterValidator
        ];
    }
}
