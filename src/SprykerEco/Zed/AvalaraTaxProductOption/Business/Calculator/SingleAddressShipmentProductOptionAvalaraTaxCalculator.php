<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator;

use ArrayObject;
use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;

class SingleAddressShipmentProductOptionAvalaraTaxCalculator extends AbstractProductOptionAvalaraTaxCalculator
{
    /**
     * @param \Generated\Shared\Transfer\CalculableObjectTransfer $calculableObjectTransfer
     * @param \Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
     *
     * @return \Generated\Shared\Transfer\CalculableObjectTransfer
     */
    public function calculateTax(
        CalculableObjectTransfer $calculableObjectTransfer,
        AvalaraCreateTransactionResponseTransfer $avalaraCreateTransactionResponseTransfer
    ): CalculableObjectTransfer {
        $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku = $this->getProductOptionAvalaraTransactionLineTransfersIndexedByItemSkuAndProductOptionSku(
            $avalaraCreateTransactionResponseTransfer->getTransactionOrFail()->getLines()
        );

        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            $productOptionTransfers = $itemTransfer->getProductOptions();
            if (!array_key_exists($itemTransfer->getSkuOrFail(), $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku)) {
                continue;
            }

            $this->calculateTaxForProductOptions(
                $productOptionTransfers,
                $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku[$itemTransfer->getSkuOrFail()]
            );

            $this->setDefaultZeroTaxRateForProductOptions($itemTransfer);
        }

        return $calculableObjectTransfer;
    }

    /**
     * @param \ArrayObject|\Generated\Shared\Transfer\ProductOptionTransfer[] $productOptionTransfers
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $productOptionAvalaraTransactionLineTransfersMappedBySku
     *
     * @return void
     */
    protected function calculateTaxForProductOptions(ArrayObject $productOptionTransfers, array $productOptionAvalaraTransactionLineTransfersMappedBySku): void
    {
        foreach ($productOptionTransfers as $productOptionTransfer) {
            if (!array_key_exists($productOptionTransfer->getSkuOrFail(), $productOptionAvalaraTransactionLineTransfersMappedBySku)) {
                continue;
            }

            $productOptionAvalaraTransactionLineTransfer = $productOptionAvalaraTransactionLineTransfersMappedBySku[$productOptionTransfer->getSkuOrFail()];

            $taxRate = $this->sumTaxRateFromTransactionLineDetails($productOptionAvalaraTransactionLineTransfer->getDetailsOrFail());
            $taxAmount = $this->moneyFacade->convertDecimalToInteger($productOptionAvalaraTransactionLineTransfer->getTaxOrFail()->toFloat());

            $productOptionTransfer
                ->setTaxRate($taxRate)
                ->setSumTaxAmount($taxAmount);
        }
    }
}
