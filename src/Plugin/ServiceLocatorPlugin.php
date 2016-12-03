<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\ServiceBus\Plugin;

use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\MessageBus;

/**
 * This plugin can be used to lazy load message handlers.
 * Initialize it with a Interop\Container\ContainerInterface
 * and route your messages to the service id only.
 */
class ServiceLocatorPlugin implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /**
     * @var ContainerInterface
     */
    protected $serviceLocator;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function attach(ActionEventEmitter $events): void
    {
        $this->trackHandler($events->attachListener(
            MessageBus::EVENT_DISPATCH,
            $this,
            MessageBus::PRIORITY_LOCATE_HANDLER
        ));
    }

    public function __invoke(ActionEvent $actionEvent): void
    {
        $messageHandlerAlias = $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER);

        if (is_string($messageHandlerAlias) && $this->serviceLocator->has($messageHandlerAlias)) {
            $actionEvent->setParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER, $this->serviceLocator->get($messageHandlerAlias));
        }

        // for event bus only
        $eventListeners = [];
        foreach ($actionEvent->getParam(EventBus::EVENT_PARAM_EVENT_LISTENERS, []) as $eventListenerAlias) {
            if (is_string($eventListenerAlias) && $this->serviceLocator->has($eventListenerAlias)) {
                $eventListeners[] = $this->serviceLocator->get($eventListenerAlias);
            }
        }
        if (! empty($eventListeners)) {
            $actionEvent->setParam(EventBus::EVENT_PARAM_EVENT_LISTENERS, $eventListeners);
        }
    }
}
