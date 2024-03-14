<?php declare(strict_types=1);

namespace Go2FlowHidePrices\Subscriber;

use Go2FlowHidePrices\Service\PriceBlockValidationService;
use Go2FlowHidePrices\Struct\HidePriceStruct;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Shopware\Storefront\Page\Wishlist\WishlistPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{

    private PriceBlockValidationService $priceBlockValidationService;

    public function __construct(
        PriceBlockValidationService $priceBlockValidationService,
    ) {
        $this->priceBlockValidationService = $priceBlockValidationService;
    }
    public static function getSubscribedEvents()
    {
        return [
            'sales_channel.'.ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded',
            BeforeLineItemAddedEvent::class         => 'onAddToCart',
            ProductPageLoadedEvent::class           => 'onPageLoaded',
            NavigationPageLoadedEvent::class        => 'onPageLoaded',
            SearchPageLoadedEvent::class            => 'onPageLoaded',
            SuggestPageLoadedEvent::class           => 'onPageLoaded',
            WishlistPageLoadedEvent::class          => 'onPageLoaded',
            MinimalQuickViewPageLoadedEvent::class  => 'onPageLoaded'
        ];
    }

    public function onProductLoaded(SalesChannelEntityLoadedEvent $event): void
    {
        if ($this->priceBlockValidationService->shouldBlockPrices($event->getSalesChannelContext())) {
            /** @var SalesChannelProductEntity $product */
            foreach ($event->getEntities() as $product) {
                if (
                    $this->priceBlockValidationService->shouldRemovePriceFromProduct(
                        $product,
                        $event->getSalesChannelContext()
                    )
                ) {
                    /** @var Price $price */
                    $product->addExtension('g2f_hide_price', new HidePriceStruct());
                    foreach ($product->getPrice() as $price) {
                        $price->setGross(0);
                        $price->setNet(0);
                    }
                    $price = $product->getCalculatedPrice();
                    $price->overwrite(0, 0, $price->getCalculatedTaxes());
                    /** @var CalculatedPrice $price */
                    foreach ($product->getCalculatedPrices() as $price) {
                        $price->overwrite(0, 0, $price->getCalculatedTaxes());
                    }
                    $price = $product->getCalculatedCheapestPrice();
                    $price->overwrite(0, 0, $price->getCalculatedTaxes());
                    /** @var CalculatedCheapestPrice $price */
                    foreach ($product->getCalculatedCheapestPrice() as $price) {
                        $price->overwrite(0, 0, $price->getCalculatedTaxes());
                    }
                }
            }
        }
    }

    public function onAddToCart(BeforeLineItemAddedEvent $event): void
    {
        if (
            $this->priceBlockValidationService->shouldRemoveItem(
                $event->getLineItem()->getId(),
                $event->getSalesChannelContext()
            )
        ) {
            $event->getCart()->remove($event->getLineItem()->getId());
        }
    }

    public function onPageLoaded(PageLoadedEvent $event)
    {
        $event->getPage()->assign([
            'go2flowHidePrices' => $this->priceBlockValidationService->shouldBlockPrices(
                $event->getSalesChannelContext()
            )
        ]);
    }
}