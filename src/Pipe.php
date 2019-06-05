<?php

namespace Mshule\LaravelPipes;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mshule\LaravelPipes\Matching\CueValidator;
use Mshule\LaravelPipes\Matching\KeyValidator;
use Mshule\LaravelPipes\Matching\PatternValidator;
use Mshule\LaravelPipes\Exceptions\NoKeysSpecifiedException;

class Pipe extends Route
{
    /**
     * The key the pipe respond to.
     *
     * @var string
     */
    public $key;

    /**
     * The cue pattern the pipe responds to.
     *
     * @var string
     */
    public $cue;

    /**
     * The alias cues a pipe responds to.
     *
     * @var array
     */
    public $alias = [];

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
     * @param string         $key
     */
    public function __construct($cue, $action, $key = '')
    {
        parent::__construct(['GET'], $cue, $action);

        $this->cue = strtolower($cue);
        $this->key = strtolower($key);
        $this->alias = Arr::get($this->action, 'alias', []);
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
     * Get the key the pipe responds to.
     *
     * @return array
     */
    public function key()
    {
        if ('placeholder' === $this->key) {
            $this->key = null;
        }

        if ($this->key) {
            return $this->key;
        }

        $key = $this->key ?? Arr::get($this->action, 'key', null);

        if (! $key) {
            throw new NoKeysSpecifiedException("No key were defined for {$this->pipe->cue()}");
        }

        return $this->key = strtolower($key);
    }

    /**
     * Bind the pipe to a given request for execution.
     *
     * @param \Illuminate\Http\Request $request
     *
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
            new KeyValidator(), new CueValidator(), new PatternValidator(),
        ];
    }

    /**
     * Get the full path for the pipe with the replaced attributes from the request.
     *
     * @param Request $request
     */
    public function path(Request $request)
    {
        $replacements = collect($this->parameterNames())->map(function ($param) use ($request) {
            return $request->{$this->paramKey($param)};
        })->toArray();

        $path = preg_replace_array('/\\{[a-zA-Z]+\\}/', $replacements, $this->uri());

        return Str::startsWith($path, '/') ? $path : '/'.$path;
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param array|string $name
     *
     * @return $this
     */
    public function alias($name)
    {
        foreach ($this->parseAlias($name) as $name) {
            $this->alias[] = strtolower($name);
        }

        return $this;
    }

    /**
     * Parse arguments to the alias method into an array.
     *
     * @param array|string $name
     * @param string       $expression
     *
     * @return array
     */
    protected function parseAlias($name)
    {
        return is_array($name) ? $name : [$name];
    }

    /**
     * Checks if a pipe does have alias specified.
     *
     * @param string|null $key
     *
     * @return bool
     */
    public function hasAlias($key = null)
    {
        if (is_null($key)) {
            return (bool) count($this->getAlias());
        }

        return in_array($key, $this->getAlias());
    }

    /**
     * Get all alias of a pipe.
     *
     * @return array
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Checks if cue of this pipe starts with a placeholder.
     *
     * @return bool
     */
    public function cueStartsWithPlaceholder()
    {
        return Str::startsWith($this->cue(), '{');
    }

    /**
     * Checks if the pipes cue contains placeholders.
     *
     * @return bool
     */
    public function cueContainsPlaceholder()
    {
        return Str::contains($this->cue(), ['{', '}']);
    }

    /**
     * Checks if cue only contains one placeholder.
     *
     * @return bool
     */
    public function cueIsPlaceholder()
    {
        return preg_match('/^\\{[A-Za-z]+\\}$/', $this->cue());
    }

    /**
     * Get param key for the path matching of a pipe.
     *
     * @return string
     */
    public function paramKey($param)
    {
        if ($this->key() === resolve('pipe_any')) {
            return $param;
        }

        return $this->cueIsPlaceholder()
                ? $this->key()
                : $param ?? $this->key();
    }
}
