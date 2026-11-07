<?php

namespace App\Filament\Support;

/**
 * For the Create/Edit pages of the detail resources: copies technician and
 * price from the form onto the parent maintenance record.
 */
trait SavesParentRecordFields
{
    protected function afterCreate(): void
    {
        // on create the fields start empty, so only write what was filled in;
        // never wipe a price that was already set on the maintenance record
        $this->writeParentRecordFields(onlyFilled: true);
    }

    protected function afterSave(): void
    {
        // on edit the fields were pre-filled from the record, so write as-is
        // (this also lets an admin clear a price)
        $this->writeParentRecordFields(onlyFilled: false);
    }

    private function writeParentRecordFields(bool $onlyFilled): void
    {
        $parent = $this->record->maintenanceRecord()->first();

        if ($parent === null) {
            return;
        }

        $values = [];

        foreach (ParentRecordFields::FIELDS as $field) {
            if (! array_key_exists($field, $this->data)) {
                continue;
            }

            $value = $this->data[$field];
            $value = $value === '' ? null : $value;

            if ($onlyFilled && $value === null) {
                continue;
            }

            $values[$field] = $value;
        }

        if ($values !== []) {
            $parent->update($values);
        }
    }
}
