<?php declare(strict_types=1);

namespace Shopware\Storefront\Test\Page\Checkout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Framework\Routing\InternalRequest;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Storefront\Framework\Page\PageLoaderInterface;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoader;
use Shopware\Storefront\Test\Page\StorefrontPageTestBehaviour;

class FinishPageTest extends TestCase
{
    use IntegrationTestBehaviour,
        StorefrontPageTestBehaviour;

    public function testItRequiresAOrderId(): void
    {
        $request = new InternalRequest();
        $context = $this->createCheckoutContextWithNavigation();

        $this->expectParamMissingException('orderId');
        $this->getPageLoader()->load($request, $context);
    }

    public function testLoginRequirement(): void
    {
        $this->assertLoginRequirement(['orderId' => Uuid::uuid4()->getHex()]);
    }

    public function testMissingOrderThrows(): void
    {
        $request = new InternalRequest(['orderId' => 'foo']);
        $context = $this->createCheckoutContextWithLoggedInCustomerAndWithNavigation();

        $this->expectException(OrderNotFoundException::class);
        $this->getPageLoader()->load($request, $context);
    }

    public function testFinishPageLoading(): void
    {
        $context = $this->createCheckoutContextWithLoggedInCustomerAndWithNavigation();
        $orderId = $this->placeRandomOrder($context);
        $request = new InternalRequest(['orderId' => $orderId]);

        /** @var CheckoutFinishPageLoadedEvent $event */
        $event = null;
        $this->catchEvent(CheckoutFinishPageLoadedEvent::NAME, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertInstanceOf(CheckoutFinishPage::class, $page);
        static::assertSame(13.04, $page->getOrder()->getPrice()->getNetPrice());
        self::assertPageEvent(CheckoutFinishPageLoadedEvent::class, $event, $context, $request, $page);
    }

    /**
     * @return CheckoutFinishPageLoader
     */
    protected function getPageLoader(): PageLoaderInterface
    {
        return $this->getContainer()->get(CheckoutFinishPageLoader::class);
    }
}