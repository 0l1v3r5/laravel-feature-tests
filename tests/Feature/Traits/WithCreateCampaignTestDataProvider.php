<?php

namespace Tests\Feature\Traits;

use App\Models\CampaignCategory;
use App\Models\Country;
use App\Traits\WithCountries;

/**
 * Create campaign test data provider
 */
trait WithCreateCampaignTestDataProvider
{
    use WithCountries;

    public function createCampaignValidFormDataProvider()
    {
        return [
            ['BJ', 'address', 'Animaux', 'title 1', 2000],
            ['TG', 'address 2', 'Famille', 'title 2', 5000],
        ];
    }

    public function createCampaignInvalidFormDataProvider()
    {
        return [
            ['BJX', 'add', 'Anim', 'title 1', 200],
            ['TGDE', 'xade', 'Familys', 'title 2', 1000000000000000],
        ];
    }

    public function firstStepValidFormDataProvider()
    {
        return [
            ['BJ', 'address', 'Animaux', 'title 1'],
            ['TG', 'address 2', 'Famille', 'title 2'],
        ];
    }

    public function firstStepInvalidFormDataProvider()
    {
        return [
            ['BJX', 'addre', 'Animalx', 'title'],
            ['TGXSS', 'addss', 'Anilx', 'title'],
        ];
    }

    protected function seedCountriesData(): void
    {
        $countries = $this->getIndependentCountryNativeNames()->take(20);
        $supportedCountryCodes = [
            'BJ',
            'TG',
            'BF',
        ];

        foreach ($countries as $country) {
            $isSupported = in_array($country['cca2'], $supportedCountryCodes) ? true : false;
            Country::factory()->create([
                'code' => $country['cca2'],
                'name' => $country['name'],
                'is_supported' => $isSupported,
            ]);
        }
    }

    protected function seedCampaignCategoriesData(): void
    {
        $campaignCategoryNames = [
            'Animaux',
            'Famille',
            'Bénévolat',
        ];

        foreach ($campaignCategoryNames as $name) {
            CampaignCategory::factory()->create([
                'name' => $name,
            ]);
        }
    }
}
