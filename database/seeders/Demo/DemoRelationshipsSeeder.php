<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class DemoRelationshipsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        // Link programs to standards
        foreach ($this->context->programs as $index => $program) {
            if (isset($this->context->standards[$index % count($this->context->standards)])) {
                $program->standards()->attach($this->context->standards[$index % count($this->context->standards)]->id);
            }
        }

        // Link programs to risks
        foreach ($this->context->risks as $index => $risk) {
            $programIndex = $index % count($this->context->programs);
            $this->context->programs[$programIndex]->risks()->attach($risk->id);
        }

        // Link implementations to applications
        foreach ($this->context->implementations as $index => $implementation) {
            if (isset($this->context->applications[$index])) {
                $implementation->applications()->attach($this->context->applications[$index]->id);
            }
        }

        // Link implementations to vendors
        foreach (array_slice($this->context->implementations, 0, 5) as $index => $implementation) {
            if (isset($this->context->vendors[$index])) {
                $implementation->vendors()->attach($this->context->vendors[$index]->id);
            }
        }

        // Link controls to policies
        foreach ($this->context->controls as $index => $control) {
            if (isset($this->context->policies[$index % count($this->context->policies)])) {
                $control->policies()->attach($this->context->policies[$index % count($this->context->policies)]->id);
            }
        }

        // Link implementations to policies
        foreach ($this->context->implementations as $index => $implementation) {
            if (isset($this->context->policies[$index % count($this->context->policies)])) {
                $implementation->policies()->attach($this->context->policies[$index % count($this->context->policies)]->id);
            }
        }

        // Link risks to implementations
        foreach ($this->context->risks as $index => $risk) {
            if (isset($this->context->implementations[$index % count($this->context->implementations)])) {
                $risk->implementations()->attach($this->context->implementations[$index % count($this->context->implementations)]->id);
            }
        }
    }
}
