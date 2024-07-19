<?php

namespace Go2FlowHidePrices\Service;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PriceBlockValidationService
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $productRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $productRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->productRepository = $productRepository;
    }

    public function shouldBlockPrices(SalesChannelContext $salesChannelContext):bool
    {
        $isActive = $this->systemConfigService->getBool(
            'Go2FlowHidePrices.config.active',
            $salesChannelContext->getSalesChannelId());
        $customerGroupId = $salesChannelContext->getCurrentCustomerGroup()->getId();
        $allowedGroups = $this->systemConfigService->get(
            'Go2FlowHidePrices.config.customerGroups',
            $salesChannelContext->getSalesChannelId());
        return $isActive && !in_array($customerGroupId, $allowedGroups);
    }

    public function shouldRemovePriceFromProduct(
        ProductEntity $product,
        SalesChannelContext $salesChannelContext
    ): bool
    {
        $categoryMatch = false;
        $productMatch = false;

        $allowedCategories = $this->systemConfigService->get(
            'Go2FlowHidePrices.config.categories',
            $salesChannelContext->getSalesChannelId());
        $categories = $product->getCategoryIds();
        if (
            $categories
            && $allowedCategories
            && count(array_intersect($allowedCategories, $categories))
        ) {
            $categoryMatch = true;
        }
        $allowedProducts = $this->systemConfigService->get(
            'Go2FlowHidePrices.config.products',
            $salesChannelContext->getSalesChannelId());
        if (
            $allowedProducts
            && in_array($product->getId(), $allowedProducts)
        ) {
            $productMatch = true;
        }
        return !$categoryMatch && !$productMatch;
    }

    public function shouldRemoveItem(string $productId, SalesChannelContext $salesChannelContext):bool
    {
        if ($this->shouldBlockPrices($salesChannelContext)) {
            $criteria = new Criteria([$productId]);
            $product = $this->productRepository->search($criteria, $salesChannelContext->getContext())->first();
            return (
                $product
                && $this->shouldRemovePriceFromProduct($product, $salesChannelContext)
            );
        }
        return false;
    }
}