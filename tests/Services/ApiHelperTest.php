<?php

namespace Osiset\ShopifyApp\Test\Services;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Exceptions\ApiException;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;

class ApiHelperTest extends TestCase
{
    protected $api;

    public function setUp(): void
    {
        parent::setUp();

        $this->api = $this->app->make(IApiHelper::class);
    }

    public function testMake(): void
    {
        // Cover the full make
        $this->app['config']->set('shopify-app.api_rate_limiting_enabled', true);

        // Make it
        $api = $this->api->make()->getApi();

        $this->assertInstanceOf(BasicShopifyAPI::class, $api);
        $this->assertEquals($this->app['config']->get('shopify-app.api_secret'), null);
        $this->assertEquals($this->app['config']->get('shopify-app.api_version'), '2020-01');
    }

    public function testSetAndGetApi(): void
    {
        // Make it and set it
        $api = $this->api->make();
        $this->api->setApi($api->getApi());

        $this->assertInstanceOf(BasicShopifyAPI::class, $this->api->getApi());
    }

    public function testWithApi(): void
    {
        // Make it and set it
        $api = $this->api->make();

        // Use it
        $called = false;
        $this->api->withApi($api->getApi(), function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testBuildAuthUrl(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        $this->assertNotEmpty(
            $shop->apiHelper()->buildAuthUrl(AuthMode::OFFLINE(), 'read_content')
        );
    }

    public function testGetScriptTags(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_script_tags']);

        $this->assertIsArray(
            $shop->apiHelper()->getScriptTags()
        );
    }

    public function testCreateScriptTags(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        $this->assertIsObject(
            $shop->apiHelper()->createScriptTag([])
        );
    }

    public function testGetCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_application_charge']);

        $this->assertIsArray(
            $shop->apiHelper()->getCharge(ChargeType::CHARGE(), new ChargeReference(1234))
        );
    }

    public function testActivateCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges_activate']);

        $this->assertIsArray(
            $shop->apiHelper()->activateCharge(ChargeType::RECURRING(), new ChargeReference(1234))
        );
    }

    public function testCreateCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges']);

        $this->assertIsArray(
            $shop->apiHelper()->createCharge(
                ChargeType::RECURRING(),
                new PlanDetailsTransfer(
                    'Test',
                    12.00,
                    true,
                    7,
                    null,
                    null,
                    null
                )
            )
        );
    }

    public function testGetWebhooks(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_webhooks']);

        $this->assertIsArray(
            $shop->apiHelper()->getWebhooks()
        );
    }

    public function testCreateWebhook(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_webhook']);

        $this->assertIsArray(
            $shop->apiHelper()->createWebhook([])
        );
    }

    public function testDeleteWebhook(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        $this->assertInstanceOf(
            ResponseAccess::class,
            $shop->apiHelper()->deleteWebhook(1)
        );
    }

    public function testCreateUsageCharge(): void
    {
        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges_usage_charges']);

        $tranfer = new UsageChargeDetailsTransfer();
        $tranfer->chargeReference = new ChargeReference(1);
        $tranfer->price = 12.00;
        $tranfer->description = 'Hello!';

        $this->assertIsArray(
            $shop->apiHelper()->createUsageCharge($tranfer)
        );
    }

    public function testErrors(): void
    {
        $this->expectException(ApiException::class);

        // Create a shop
        $shop = factory($this->model)->create();

        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty_with_error']);

        $shop->apiHelper()->deleteWebhook(1);
    }
}
