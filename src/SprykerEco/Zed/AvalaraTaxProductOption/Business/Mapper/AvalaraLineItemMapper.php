<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\AvalaraAddressTransfer;
use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\StockAddressTransfer;
use SprykerEco\Zed\AvalaraTaxProductOption\Dependency\Facade\AvalaraTaxProductOptionToMoneyFacadeInterface;

class AvalaraLineItemMapper implements AvalaraLineItemMapperInterface
{
    /**
     * @var string
     */
    public const PRODUCT_OPTION_AVALARA_LINE_TYPE = 'cart-item-option';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     *
     * @var string
     */
    protected const PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPTO
     *
     * @var string
     */
    protected const AVALARA_SHIP_TO_ADDRESS_TYPE = 'ShipTo';

    /**
     * @uses \Avalara\TransactionAddressType::C_SHIPFROM
     *
     * @var string
     */
    protected const AVALARA_SHIP_FROM_ADDRESS_TYPE = 'ShipFrom';

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
            ->setTaxCode($productOptionTransfer->getAvalaraTaxCode() ?? '')
            ->setQuantity($productOptionTransfer->getQuantityOrFail())
            ->setAmount($this->calculateProductOptionAmount($productOptionTransfer))
            ->setItemCode($productOptionTransfer->getSkuOrFail())
            ->setReference1(static::PRODUCT_OPTION_AVALARA_LINE_TYPE)
            ->setReference2($itemTransfer->getSkuOrFail())
            ->setTaxIncluded($this->isTaxIncluded($priceMode));

        if (!$itemTransfer->getShipment() && !$itemTransfer->getWarehouse()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraLineItemTransfer = $this->mapItemTransferShippingAddressToAvalaraLineItemTransfer($itemTransfer, $avalaraLineItemTransfer);
        $avalaraLineItemTransfer = $this->mapItemTransferStockAddressToAvalaraItemTransfer($itemTransfer, $avalaraLineItemTransfer);

        return $avalaraLineItemTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    protected function mapItemTransferShippingAddressToAvalaraLineItemTransfer(
        ItemTransfer $itemTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer
    ): AvalaraLineItemTransfer {
        if (!$itemTransfer->getShipment()) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_TO_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapShipmentTransferToAvalaraAddressTransfer(
            $itemTransfer->getShipmentOrFail(),
            $avalaraShippingAddressTransfer,
        );

        return $avalaraLineItemTransfer->setShippingAddress($avalaraShippingAddressTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    protected function mapItemTransferStockAddressToAvalaraItemTransfer(
        ItemTransfer $itemTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer
    ): AvalaraLineItemTransfer {
        if (!$itemTransfer->getWarehouse()) {
            return $avalaraLineItemTransfer;
        }

        $stockAddressTransfer = $itemTransfer->getWarehouseOrFail()->getAddress();
        if ($stockAddressTransfer === null) {
            return $avalaraLineItemTransfer;
        }

        $avalaraShippingAddressTransfer = (new AvalaraAddressTransfer())->setType(static::AVALARA_SHIP_FROM_ADDRESS_TYPE);
        $avalaraShippingAddressTransfer = $this->mapStockAddressTransferToAvalaraAddressTransfer(
            $stockAddressTransfer,
            $avalaraShippingAddressTransfer,
        );

        return $avalaraLineItemTransfer->setSourceAddress($avalaraShippingAddressTransfer);
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
        return $avalaraAddressTransfer->setAddress($shipmentTransfer->getShippingAddressOrFail());
    }

    /**
     * @param \Generated\Shared\Transfer\StockAddressTransfer $stockAddressTransfer
     * @param \Generated\Shared\Transfer\AvalaraAddressTransfer $avalaraAddressTransfer
     *
     * @return \Generated\Shared\Transfer\AvalaraAddressTransfer
     */
    protected function mapStockAddressTransferToAvalaraAddressTransfer(
        StockAddressTransfer $stockAddressTransfer,
        AvalaraAddressTransfer $avalaraAddressTransfer
    ): AvalaraAddressTransfer {
        $addressTransfer = (new AddressTransfer())->fromArray($stockAddressTransfer->toArray(), true);
        $addressTransfer->setIso2Code($stockAddressTransfer->getCountryOrFail()->getIso2CodeOrFail());

        return $avalaraAddressTransfer->setAddress($addressTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductOptionTransfer $productOptionTransfer
     *
     * @return float
     */
    protected function calculateProductOptionAmount(ProductOptionTransfer $productOptionTransfer): float
    {
        $productOptionAmount = $productOptionTransfer->getSumPriceOrFail() - $productOptionTransfer->getSumDiscountAmountAggregation() ?? 0;

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
