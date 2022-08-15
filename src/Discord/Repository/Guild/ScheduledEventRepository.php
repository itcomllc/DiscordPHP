<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\resolve;

/**
 * Contains scheduled events to guilds.
 *
 * @see \Discord\Parts\Guild\ScheduledEvent
 * @see \Discord\Parts\Guild\Guild
 */
class ScheduledEventRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'get' => Endpoint::GUILD_SCHEDULED_EVENT,
        'create' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'update' => Endpoint::GUILD_SCHEDULED_EVENT,
        'delete' => Endpoint::GUILD_SCHEDULED_EVENT,
    ];

    /**
     * @inheritdoc
     */
    protected $class = ScheduledEvent::class;

    /**
     * @inheritdoc
     *
     * @param bool $with_user_count Whether to include number of users subscribed to each event
     */
    public function fetch(string $id, bool $fresh = false, bool $with_user_count = false): ExtendedPromiseInterface
    {
        if (! $with_user_count) {
            return parent::fetch($id, $fresh);
        }

        if (! $fresh && $part = $this->get($this->discrim, $id)) {
            if (isset($part->user_count)) {
                return resolve($part);
            }
        }

        $part = $this->factory->part($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        $endpoint->addQuery('with_user_count', $with_user_count);

        return $this->http->get($endpoint)->then(function ($response) use ($part, $id) {
            $part->fill(array_merge($this->vars, (array) $response));
            $part->created = true;

            return $this->cache->set($id, $part)->then(function ($success) use ($part) {
                return $part;
            });
        });
    }
}
