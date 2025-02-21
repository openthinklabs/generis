<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015-2022 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\oatbox\event;

use common_Logger;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceNotFoundException;
use Throwable;

/**
 * The simple placeholder ServiceManager
 * @author Joel Bout <joel@taotesting.com>
 */
class EventManager extends ConfigurableService
{
    public const SERVICE_ID = 'generis/event';

    /**
     * @deprecated use SERVICE_ID
     */
    public const CONFIG_ID = 'generis/event';

    public const OPTION_LISTENERS = 'listeners';

    /**
     * Dispatch an event and trigger its listeners
     *
     * @param mixed $event either an Event object or a string
     * @param array $params
     */
    public function trigger($event, $params = [])
    {
        $container = $this->getServiceManager()->getContainer();

        $event = is_object($event) ? $event : new GenericEvent($event, $params);

        foreach ($this->getListeners($event) as $callback) {
            $callbackName = $callback;

            if (is_array($callback) && count($callback) == 2) {
                [$key, $function] = $callback;

                if (is_object($key)) {
                    $callbackName = sprintf('%s::%s', get_class($key), $function);
                }

                if (is_string($key)) {
                    $callbackName = sprintf('%s::%s', $key, $function);

                    try {
                        $service = $container->get($key);
                        $callback = [$service, $function];
                    } catch (ServiceNotFoundException $e) {
                        //do nothing
                    }
                }
            }

            if (!is_callable($callback)) {
                common_Logger::w(
                    sprintf(
                        'Event manager cannot call %s because it is not a callable. '
                        . 'Notice, that classes registered in DI container cannot be executed during the installation',
                        $callbackName
                    )
                );

                continue;
            }

            call_user_func($callback, $event);
        }
    }

    /**
     * Attach a Listener to one or multiple events
     *
     * @param mixed $event either an Event object or a string
     * @param callable $callback
     */
    public function attach($event, $callback)
    {
        $events = is_array($event) ? $event : [$event];
        $listeners = $this->getOption(self::OPTION_LISTENERS);
        foreach ($events as $event) {
            $eventObject = is_object($event) ? $event : new GenericEvent($event);
            if (!isset($listeners[$eventObject->getName()])) {
                $listeners[$eventObject->getName()] = [];
            }

            if (!in_array($callback, $listeners[$eventObject->getName()], true)) {
                $listeners[$eventObject->getName()][] = $callback;
            }
        }
        $this->setOption(self::OPTION_LISTENERS, $listeners);
    }

    /**
     * remove listener from an event and delete event if it dosn't have any listeners
     * @param array $listeners
     * @param string $eventName
     * @param callable $callback
     * @return array
     */
    protected function removeListener(array $listeners, $eventObject, $callback)
    {
        if (isset($listeners[$eventObject->getName()])) {
            if (($index = array_search($callback, $listeners[$eventObject->getName()])) !== false) {
                unset($listeners[$eventObject->getName()][$index]);
                if (empty($listeners[$eventObject->getName()])) {
                    unset($listeners[$eventObject->getName()]);
                } else {
                    $listeners[$eventObject->getName()] = array_values($listeners[$eventObject->getName()]);
                }
            }
        }
        return $listeners;
    }


    /**
     * Detach a Listener from one or multiple events
     *
     * @param mixed $event either an Event object or a string
     * @param Callable $callback
     */
    public function detach($event, $callback)
    {
        $events = is_array($event) ? $event : [$event];
        $listeners = $this->getOption(self::OPTION_LISTENERS);
        foreach ($events as $event) {
            $eventObject = is_object($event) ? $event : new GenericEvent($event);
            $listeners = $this->removeListener($listeners, $eventObject, $callback);
        }
        $this->setOption(self::OPTION_LISTENERS, $listeners);
    }

    /**
     * Get all Listeners listening to this kind of event
     *
     * @param Event $eventObject
     * @return Callable[] listeners associated with this event
     */
    protected function getListeners(Event $eventObject)
    {
        $listeners = $this->getOption(self::OPTION_LISTENERS);
        return isset($listeners[$eventObject->getName()])
            ? $listeners[$eventObject->getName()]
            : [];
    }
}
