<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Calculator;

use Generated\Shared\Transfer\AvalaraCreateTransactionResponseTransfer;
use Generated\Shared\Transfer\AvalaraTransactionLineTransfer;
use Generated\Shared\Transfer\AvalaraTransactionTransfer;
use Generated\Shared\Transfer\CalculableObjectTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;

class MultiAddressShipmentProductOptionAvalaraTaxCalculator extends AbstractProductOptionAvalaraTaxCalculator
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
        $avalaraTransactionTransfer = $avalaraCreateTransactionResponseTransfer->getTransactionOrFail();

        $zipCodeRegionNameMap = $this->getRegionZipCodeMap($avalaraTransactionTransfer);
        $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku = $this->getProductOptionAvalaraTransactionLineTransfersIndexedByItemSkuAndProductOptionSku(
            $avalaraCreateTransactionResponseTransfer->getTransactionOrFail()->getLines()
        );

        foreach ($calculableObjectTransfer->getItems() as $itemTransfer) {
            if (!array_key_exists($itemTransfer->getSkuOrFail(), $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku)) {
                continue;
            }

            $this->calculateProductOptionsTax(
                $itemTransfer,
                $productOptionAvalaraTransactionLineTransfersMappedByItemSkuAndProductOptionSku[$itemTransfer->getSkuOrFail()],
                $zipCodeRegionNameMap
            );
        }

        return $calculableObjectTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $avalaraTransactionLineTransfers
     * @param string[] $zipCodeRegionNameMap
     *
     * @return void
     */
    protected function calculateProductOptionsTax(
        ItemTransfer $itemTransfer,
        array $avalaraTransactionLineTransfers,
        array $zipCodeRegionNameMap
    ): void {
        foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
            $productOptionAvalaraTransactionLineTransfer = $this->findAvalaraLineItemTransferForItemTransfer(
                $itemTransfer,
                $productOptionTransfer,
                $avalaraTransactionLineTransfers,
                $zipCodeRegionNameMap
            );

            if (!$productOptionAvalaraTransactionLineTransfer) {
                continue;
            }

            $taxRate = $this->sumTaxRateFromTransactionLineDetails($productOptionAvalaraTransactionLineTransfer->getDetailsOrFail());
            $taxAmount = $this->moneyFacade->convertDecimalToInteger($productOptionAvalaraTransactionLineTransfer->getTaxOrFail()->toFloat());

            $productOptionTransfer
                ->setTaxRate($taxRate)
                ->setSumTaxAmount($taxAmount);
        }

        $this->setDefaultZeroTaxRateForProductOptions($itemTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\ProductOptionTransfer $productOptionTransfer
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer[] $avalaraTransactionLineTransfers
     * @param string[] $zipCodeRegionNameMap
     *
     * @return \Generated\Shared\Transfer\AvalaraTransactionLineTransfer|null
     */
    protected function findAvalaraLineItemTransferForItemTransfer(
        ItemTransfer $itemTransfer,
        ProductOptionTransfer $productOptionTransfer,
        array $avalaraTransactionLineTransfers,
        array $zipCodeRegionNameMap
    ): ?AvalaraTransactionLineTransfer {
        foreach ($avalaraTransactionLineTransfers as $avalaraTransactionLineTransfer) {
            if (!$avalaraTransactionLineTransfer->getQuantityOrFail()->equals($productOptionTransfer->getQuantityOrFail())) {
                continue;
            }

            $itemShipmentAddressZipCode = $itemTransfer->getShipmentOrFail()->getShippingAddressOrFail()->getZipCodeOrFail();
            if (!$this->isSameRegion($zipCodeRegionNameMap, $itemShipmentAddressZipCode, $avalaraTransactionLineTransfer)) {
                continue;
            }

            return $avalaraTransactionLineTransfer;
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraTransactionTransfer $avalaraTransactionTransfer
     *
     * @return string[]
     */
    protected function getRegionZipCodeMap(AvalaraTransactionTransfer $avalaraTransactionTransfer): array
    {
        $zipCodeRegionMap = [];

        /** @var \Avalara\TransactionAddressModel[] $avalaraTransactionAddressModels */
        $avalaraTransactionAddressModels = $this->utilEncodingService->decodeJson($avalaraTransactionTransfer->getAddressesOrFail(), false);
        foreach ($avalaraTransactionAddressModels as $avalaraTransactionAddressModel) {
            if (array_key_exists($avalaraTransactionAddressModel->postalCode, $zipCodeRegionMap)) {
                continue;
            }

            $zipCodeRegionMap[$avalaraTransactionAddressModel->postalCode] = $avalaraTransactionAddressModel->region;
        }

        return $zipCodeRegionMap;
    }

    /**
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
     *
     * @return string|null
     */
    protected function extractRegionNameFromAvalaraTransactionLineTransfer(AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer): ?string
    {
        /** @var \Avalara\TransactionLineDetailModel[] $avalaraTransactionLineDetailModels */
        $avalaraTransactionLineDetailModels = $this->utilEncodingService->decodeJson(
            $avalaraTransactionLineTransfer->getDetailsOrFail(),
            false
        );

        if (!$avalaraTransactionLineDetailModels) {
            return null;
        }

        foreach ($avalaraTransactionLineDetailModels as $avalaraTransactionLineDetailModel) {
            if ($avalaraTransactionLineDetailModel->region === null) {
                continue;
            }

            return $avalaraTransactionLineDetailModel->region;
        }

        return null;
    }

    /**
     * @param string[] $zipCodeRegionNameMap
     * @param string $itemShipmentAddressZipCode
     * @param \Generated\Shared\Transfer\AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
     *
     * @return bool
     */
    protected function isSameRegion(
        array $zipCodeRegionNameMap,
        string $itemShipmentAddressZipCode,
        AvalaraTransactionLineTransfer $avalaraTransactionLineTransfer
    ): bool {
        return isset($zipCodeRegionNameMap[$itemShipmentAddressZipCode])
            && $zipCodeRegionNameMap[$itemShipmentAddressZipCode] === $this->extractRegionNameFromAvalaraTransactionLineTransfer($avalaraTransactionLineTransfer);
    }
}
