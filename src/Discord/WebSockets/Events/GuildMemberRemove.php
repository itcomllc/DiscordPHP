<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-member-remove
 */
class GuildMemberRemove extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $memberPart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Member */
                $memberPart = yield $guild->members->cachePull($data->user->id);
                --$guild->member_count;
            }

            if ($memberPart) {
                $memberPart->created = false;
            } else {
                /** @var Member */
                $memberPart = $this->factory->create(Member::class, $data);
                $memberPart->guild_id = $data->guild_id;
            }

            $this->cacheUser($data->user);

            return $memberPart;
        }, $data)->then([$deferred, 'resolve']);
    }
}
