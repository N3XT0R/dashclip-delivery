<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Factory base class with explicit Eloquent event control.
 *
 * - Default: model events / observers are disabled
 * - Opt-in via ->withEvents()
 *
 * Purpose:
 *   Deterministic data creation without implicit side effects.
 */
abstract class EventAwareFactory extends Factory
{
    protected bool $withEvents = false;

    public function withEvents(): static
    {
        $clone = clone $this;
        $clone->withEvents = true;

        return $clone;
    }

    public function withoutEvents(): static
    {
        $clone = clone $this;
        $clone->withEvents = false;

        return $clone;
    }

    public function create($attributes = [], ?Model $parent = null)
    {
        if ($this->withEvents) {
            return parent::create($attributes, $parent);
        }

        return Model::withoutEvents(
            fn() => parent::create($attributes, $parent)
        );
    }
}
