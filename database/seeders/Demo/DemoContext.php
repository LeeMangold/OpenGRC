<?php

namespace Database\Seeders\Demo;

use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;

/**
 * Shared context for Demo seeders.
 * Holds all created models that need to be referenced across seeders.
 */
class DemoContext
{
    public Faker $faker;

    /** @var array<\App\Models\User> */
    public array $users = [];

    /** @var array<\App\Models\Vendor> */
    public array $vendors = [];

    /** @var array<\App\Models\Program> */
    public array $programs = [];

    /** @var array<\App\Models\Standard> */
    public array $standards = [];

    /** @var array<\App\Models\Control> */
    public array $controls = [];

    /** @var array<\App\Models\Control> */
    public array $tscControls = [];

    /** @var array<\App\Models\Implementation> */
    public array $implementations = [];

    /** @var array<\App\Models\Policy> */
    public array $policies = [];

    /** @var array<\App\Models\Risk> */
    public array $risks = [];

    /** @var array<\App\Models\Application> */
    public array $applications = [];

    /** @var array<\App\Models\Asset> */
    public array $assets = [];

    /** @var array<\App\Models\SurveyTemplate> */
    public array $surveyTemplates = [];

    /** @var array<\App\Models\Certification> */
    public array $certifications = [];

    /** @var array<\App\Models\TrustCenterDocument> */
    public array $trustCenterDocuments = [];

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }
}
