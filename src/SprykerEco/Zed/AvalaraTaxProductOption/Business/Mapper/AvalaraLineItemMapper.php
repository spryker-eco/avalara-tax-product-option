<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper;

use Generated\Shared\Transfer\AvalaraAddressTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface;

class AvalaraLineItemMapper implements AvalaraLineItemMapperInterface
{
    public const PRODUCT_OPTION_AVALARA_LINE_TYPE = 'cart-item-option';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPTO
     */
    protected const AVALARA_SHIP_TO_ADDRESS_TYPE = 'ShipTo';

    /**
     * @var \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface
     */
    protected $moneyFacade;

    /**
     * @param \SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface $moneyFacade
     */
    public function __construct(AvalaraTaxProductOptionToMoneyFacadeInterface $moneyFacade)
    {
        $this->moneyFacade = $moneyFacade;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOptionTransfer $productOptionTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param string $priceMode
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    public function mapProductOptionTransferToAvalaraLineItemTransfer(
        ProductOptionTransfer $productOptionTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer,
        ItemTransfer $itemTransfer,
        string $priceMode
    ): AvalaraLineItemTransfer {
        $avalaraLineItemTransfer
            ->setTaxCode($productOptionTransfer->getAvalaraTaxCodeOrFail())
            ->setQuantity($productOptionTransfer->getQuantityOrFail())
            ->setAmount($this->calculateProductOptionAmount($productOptionTransfer))
            ->setItemCode($productOptionTransfer->getSkuOrFail())
            ->setReference1(static::PRODUCT_OPTION_AVALARA_LINE_TYPE)
            ->setReference2($itemTransfer->getSkuOrFail())
            ->setTaxIncluded($this->isTaxIncluded($priceMode));

        if (!$itemTransfer->getShipment()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_TO_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapShipmentTransferToAvalaraAddressTransfer(
            $itemTransfer->getShipmentOrFail(),
            $avalaraShippingAddressTransfer
        );

        return $avalaraLineItemTransfer->setShippingAddress($avalaraShippingAddressTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentTransfer $shipmentTransfer
     * @param \Generated\Shared\Transfer\AvalaraAddressTransfer $avalaraAddressTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraAddressTransfer
     */
    protected function mapShipmentTransferToAvalaraAddressTransfer(
        ShipmentTransfer $shipmentTransfer,
        AvalaraAddressTransfer $avalaraAddressTransfer
    ): AvalaraAddressTransfer {
        $avalaraAddressTransfer->setAddress($shipmentTransfer->getShippingAddressOrFail());

        return $avalaraAddressTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOptionTransfer $productOptionTransfer
     *
     * @return float
     */
    protected function calculateProductOptionAmount(ProductOptionTransfer $productOptionTransfer): float
    {
        $productOptionAmount = $productOptionTransfer->getSumPriceOrFail() - $productOptionTransfer->getSumDiscountAmountAggregationOrFail();

        return $this->moneyFacade->convertIntegerToDecimal($productOptionAmount);
    }

    /**
     * @param string $priceMode
     *
     * @return bool
     */
    protected function isTaxIncluded(string $priceMode): bool
    {
        return $priceMode === static::PRICE_MODE_GROSS;
    }
}
