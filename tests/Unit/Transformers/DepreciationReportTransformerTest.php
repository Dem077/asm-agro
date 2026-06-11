<?php

namespace Tests\Unit\Transformers;

use App\Http\Transformers\DepreciationReportTransformer;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Depreciation;
use Tests\TestCase;

class DepreciationReportTransformerTest extends TestCase
{
    public function testHandlesModelDepreciationMonthsBeingZero()
    {
        $asset = Asset::factory()->create();
        $depreciation = Depreciation::factory()->create(['months' => 0]);
        $asset->model->depreciation()->associate($depreciation);

        $transformer = new DepreciationReportTransformer;

        $result = $transformer->transformAsset($asset);

        $this->assertIsArray($result);
    }

    public function testMonthlyDepreciationAndFloorRespectMinimumValue()
    {
        $depreciation = Depreciation::factory()->create([
            'depreciation_type' => 'amount',
            'depreciation_min' => 1000,
            'months' => 36,
        ]);

        $asset = Asset::factory()
            ->laptopMbp()
            ->create([
                'category_id' => Category::factory()->assetLaptopCategory()->create(),
                'purchase_date' => now()->subYear(),
                'purchase_cost' => 4000,
            ]);

        $asset->model->update([
            'depreciation_id' => $depreciation->id,
        ]);

        $asset->load('model.depreciation');

        $result = (new DepreciationReportTransformer())->transformAsset($asset);

        $this->assertEquals('1,000.00', $result['depreciation_floor']);
        $this->assertEquals(number_format($asset->getMonthlyDepreciation(), 2, '.', ','), $result['monthly_depreciation']);
        $this->assertEquals(number_format($asset->getDepreciatedValue(), 2, '.', ','), $result['book_value']);
    }
}
