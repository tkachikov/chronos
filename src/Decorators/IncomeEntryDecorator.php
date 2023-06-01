<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Decorators;

use Laravel\Telescope\IncomingEntry;

class IncomeEntryDecorator extends IncomingEntry
{
    /**
     * @param IncomingEntry $entry
     *
     * @return $this
     */
    public function entry(IncomingEntry $entry): self
    {
        foreach (['type', 'withFamilyHash', 'user', 'tags'] as $key) {
            $prop = $key === 'withFamilyHash'
                ? 'familyHash'
                : $key;
            if (isset($entry->{$prop})) {
                $this->{$key}($entry->{$prop});
            }
        }
        $this->recordedAt = $entry->recordedAt;

        return $this;
    }

    /**
     * @param string $batchId
     *
     * @return $this
     */
    public function batchId(string $batchId): self
    {
        return isset($this->batchId)
            ? $this
            : parent::batchId($batchId);
    }
}
