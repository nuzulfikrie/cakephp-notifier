<?php

namespace CvoTechnologies\Notifier;

use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use CvoTechnologies\Notifier\Exception\MissingActionException;

/**
 * Class Notifier
 * @package CvoTechnologies\Notifier
 *
 * @mixin \CvoTechnologies\Notifier\Notification
 */
abstract class Notifier implements EventListenerInterface
{
    use LocatorAwareTrait;
    use ModelAwareTrait;

    /**
     * Notifier's name.
     *
     * @var string
     */
    static public $name;

    /**
     * Notification instance.
     *
     * @var \CvoTechnologies\Notifier\Notification
     */
    protected $_notification;

    /**
     * Cloned Notification instance for restoring instance after notification is sent by
     * notifier action.
     *
     * @var string
     */
    protected $_clonedNotification;

    /**
     * Constructor.
     *
     * @param \CvoTechnologies\Notifier\Notification|null $notification Notification instance.
     */
    public function __construct(Notification $notification = null)
    {
        if ($notification === null) {
            $notification = new Notification();
        }

        $this->_notification = $notification;
        $this->_clonedNotification = clone $notification;

        $this->modelFactory('Table', [$this->tableLocator(), 'get']);
    }

    /**
     * Returns the notifier's name.
     *
     * @return string
     */
    public function getName()
    {
        if (!static::$name) {
            static::$name = str_replace(
                'Notifier',
                '',
                join('', array_slice(explode('\\', get_class($this)), -1))
            );
        }

        return static::$name;
    }

    /**
     * Sets layout to use.
     *
     * @param string $layout Name of the layout to use.
     * @return $this object.
     */
    public function layout($layout)
    {
        $this->_notification->viewBuilder()->layout($layout);

        return $this;
    }

    /**
     * Get Notification instance's view builder.
     *
     * @return \Cake\View\ViewBuilder
     */
    public function viewBuilder()
    {
        return $this->_notification->viewBuilder();
    }

    /**
     * Magic method to forward method class to Notification instance.
     *
     * @param string $method Method name.
     * @param array $args Method arguments
     * @return $this
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->_notification, $method], $args);

        return $this;
    }

    /**
     * Sets notification view vars.
     *
     * @param string|array $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this object.
     */
    public function set($key, $value = null)
    {
        $this->_notification->viewVars(is_string($key) ? [$key => $value] : $key);

        return $this;
    }

    /**
     * Sends notification.
     *
     * @param string $action The name of the notifier action to trigger.
     * @param array $args Arguments to pass to the triggered notifier action.
     * @return array
     * @throws \CvoTechnologies\Notifier\Exception\MissingActionException
     * @throws \BadMethodCallException
     */
    public function send($action, $args = [])
    {
        if (!method_exists($this, $action)) {
            throw new MissingActionException([
                'notifier' => $this->getName() . 'Notifier',
                'action' => $action,
            ]);
        }

        if (!$this->_notification->viewBuilder()->template()) {
            $this->_notification->viewBuilder()->template($action);
        }

        call_user_func_array([$this, $action], $args);

        $result = $this->_notification->send();
        $this->reset();

        return $result;
    }

    /**
     * Reset notification instance.
     *
     * @return $this
     */
    protected function reset()
    {
        $this->_notification = clone $this->_clonedNotification;

        return $this;
    }

    /**
     * Implemented events.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
